<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommodityPriceRequest;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\CommodityPriceChange;
use App\Models\Market;
use App\Services\PredictionAutomationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MasterPriceController extends Controller
{
    public function index(Request $request): View
    {
        $changeStatus = $this->normalizedChangeStatus($request);
        $prices = CommodityPrice::query()
            ->with(['commodity.category', 'market', 'latestChange'])
            ->when($request->integer('commodity'), fn ($query, $id) => $query->where('commodity_id', $id))
            ->when($request->integer('market'), fn ($query, $id) => $query->where('market_id', $id))
            ->when($request->string('date')->toString(), fn ($query, $date) => $query->whereDate('price_date', $date))
            ->when($changeStatus === 'edited', fn ($query) => $query->whereHas('changes'))
            ->when($changeStatus === 'original', fn ($query) => $query->whereDoesntHave('changes'))
            ->orderByDesc('price_date')->orderBy('commodity_id')->orderBy('market_id')
            ->paginate(25)->withQueryString();

        return view('master.prices.index', [
            'prices' => $prices,
            'commodities' => Commodity::with('category')->orderBy('name')->get(),
            'markets' => Market::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $commodityId = $request->integer('commodity') ?: null;
        $lastDate = $commodityId ? CommodityPrice::where('commodity_id', $commodityId)->max('price_date') : CommodityPrice::max('price_date');

        return $this->form(new CommodityPrice([
            'commodity_id' => $commodityId,
            'price_date' => $lastDate ? CarbonImmutable::parse($lastDate)->addDay() : now(),
            'source' => 'Input manual',
        ]));
    }

    public function store(CommodityPriceRequest $request, PredictionAutomationService $automation): RedirectResponse
    {
        $data = $request->validated();
        $price = CommodityPrice::withTrashed()->where('commodity_id', $data['commodity_id'])
            ->where('market_id', $data['market_id'])->whereDate('price_date', $data['price_date'])->first();
        if ($price?->trashed()) {
            $price->restore();
            $price->update([...$data, 'deleted_by' => null, 'deleted_ip' => null, 'deletion_batch' => null]);
            $message = 'Data harga dipulihkan dari arsip dan diperbarui.';
        } else {
            $price = CommodityPrice::create($data);
            $message = 'Data harga berhasil ditambahkan.';
        }

        $prediction = $automation->afterPriceImport([
            'inserted' => $price->wasRecentlyCreated ? 1 : 0,
            'updated' => $price->wasRecentlyCreated ? 0 : 1,
            'changed_dates' => [$price->price_date->toDateString()],
        ], $request->user()?->id);

        return to_route('master-prices.index', ['commodity' => $price->commodity_id])->with('success', $message.' '.$prediction['message']);
    }

    public function edit(CommodityPrice $commodityPrice): View
    {
        return $this->form($commodityPrice);
    }

    public function update(CommodityPriceRequest $request, CommodityPrice $commodityPrice, PredictionAutomationService $automation): RedirectResponse
    {
        $data = $request->validated();
        $before = $commodityPrice->only(array_keys($data));
        $changed = collect($data)->filter(fn ($value, $key) => (string) ($before[$key] ?? '') !== (string) $value)->all();
        $user = $request->user();

        DB::transaction(function () use ($commodityPrice, $data, $before, $changed, $user, $request) {
            $commodityPrice->update($data);
            if ($changed) {
                CommodityPriceChange::create([
                    'commodity_price_id' => $commodityPrice->id,
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? 'Administrator lokal',
                    'user_email' => $user?->email,
                    'old_price' => (int) $before['price'],
                    'new_price' => (int) $data['price'],
                    'changes' => collect($changed)->mapWithKeys(fn ($value, $key) => [$key => ['from' => $before[$key] ?? null, 'to' => $value]])->all(),
                    'ip_address' => $request->ip(),
                ]);
            }
        });

        $prediction = $changed
            ? $automation->afterPriceImport(['inserted' => 0, 'updated' => 1, 'changed_dates' => [$commodityPrice->fresh()->price_date->toDateString()]], $request->user()?->id)
            : ['message' => 'Tidak ada perubahan yang memerlukan prediksi ulang.'];

        return to_route('master-prices.index', ['commodity' => $commodityPrice->commodity_id])->with('success', 'Data harga berhasil diperbarui. '.$prediction['message']);
    }

    public function destroy(Request $request, CommodityPrice $commodityPrice): RedirectResponse
    {
        $commodityId = $commodityPrice->commodity_id;
        $commodityPrice->update($this->deletionMetadata($request));
        $commodityPrice->delete();

        return to_route('master-prices.index', ['commodity' => $commodityId])->with('success', 'Satu data harga dipindahkan ke tempat sampah.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $scope = $request->validate(['scope' => ['required', 'in:selected,filtered']])['scope'];
        $query = CommodityPrice::query();

        if ($scope === 'selected') {
            $validated = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer', 'exists:commodity_prices,id']]);
            $query->whereIn('id', $validated['ids']);
        } else {
            $commodityId = $request->integer('commodity') ?: null;
            $marketId = $request->integer('market') ?: null;
            $date = $request->string('date')->toString();
            $changeStatus = $this->normalizedChangeStatus($request);
            if (! $commodityId && ! $marketId && ! $date && ! $changeStatus) {
                return back()->with('error', 'Pilih minimal satu filter sebelum menghapus semua hasil filter.');
            }
            $query->when($commodityId, fn ($q) => $q->where('commodity_id', $commodityId))
                ->when($marketId, fn ($q) => $q->where('market_id', $marketId))
                ->when($date, fn ($q) => $q->whereDate('price_date', $date))
                ->when($changeStatus === 'edited', fn ($q) => $q->whereHas('changes'))
                ->when($changeStatus === 'original', fn ($q) => $q->whereDoesntHave('changes'));
        }

        $metadata = $this->deletionMetadata($request);
        $query->update($metadata);
        $deleted = $query->delete();

        return back()->with('success', number_format($deleted).' data harga dipindahkan ke tempat sampah dan masih dapat dipulihkan.');
    }

    public function trash(Request $request): View
    {
        $prices = CommodityPrice::onlyTrashed()->with(['commodity.category', 'market'])
            ->when($request->integer('commodity'), fn ($query, $id) => $query->where('commodity_id', $id))
            ->when($request->integer('market'), fn ($query, $id) => $query->where('market_id', $id))
            ->when($request->string('date')->toString(), fn ($query, $date) => $query->whereDate('price_date', $date))
            ->orderByDesc('deleted_at')->paginate(25)->withQueryString();

        return view('master.prices.trash', [
            'prices' => $prices,
            'trashCount' => CommodityPrice::onlyTrashed()->count(),
            'commodities' => Commodity::orderBy('name')->get(),
            'markets' => Market::orderBy('name')->get(),
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $price = CommodityPrice::onlyTrashed()->findOrFail($id);
        $price->restore();
        $price->update(['deleted_by' => null, 'deleted_ip' => null, 'deletion_batch' => null]);

        return back()->with('success', 'Data harga berhasil dipulihkan ke daftar aktif.');
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        CommodityPrice::onlyTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'Data harga dihapus permanen dari MySQL dan tidak dapat dipulihkan.');
    }

    public function purgeTrash(): RedirectResponse
    {
        $deleted = DB::transaction(
            fn () => CommodityPrice::onlyTrashed()->forceDelete()
        );

        if ($deleted === 0) {
            return to_route('master-prices.trash')->with('success', 'Tempat sampah sudah kosong.');
        }

        return to_route('master-prices.trash')->with(
            'success',
            number_format($deleted).' data di tempat sampah berhasil dihapus permanen dari MySQL.'
        );
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']])['ids'];
        $items = CommodityPrice::onlyTrashed()->whereIn('id', $ids)->get();
        foreach ($items as $item) {
            $item->restore();
            $item->update(['deleted_by' => null, 'deleted_ip' => null, 'deletion_batch' => null]);
        }

        return back()->with('success', number_format($items->count()).' data harga berhasil dipulihkan.');
    }

    public function bulkForceDestroy(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']])['ids'];
        $deleted = CommodityPrice::onlyTrashed()->whereIn('id', $ids)->forceDelete();

        return back()->with('success', number_format($deleted).' data arsip dihapus permanen dari MySQL.');
    }

    private function deletionMetadata(Request $request): array
    {
        return [
            'deleted_by' => $request->user()?->email ?? 'Administrator lokal',
            'deleted_ip' => $request->ip(),
            'deletion_batch' => (string) Str::uuid(),
        ];
    }

    private function normalizedChangeStatus(Request $request): ?string
    {
        $status = $request->string('change_status')->toString();

        return in_array($status, ['original', 'edited'], true) ? $status : null;
    }

    private function form(CommodityPrice $price): View
    {
        return view('master.prices.form', [
            'price' => $price,
            'commodities' => Commodity::with('category')->orderBy('name')->get(),
            'markets' => Market::orderBy('name')->get(),
        ]);
    }
}
