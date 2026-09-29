<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use App\Services\PredictionAutomationService;
use App\Services\PriceImportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class PriceImportController extends Controller
{
    public function create(): View
    {
        return view('master.prices.import', [
            'markets' => Market::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function downloadTemplate(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate(['market' => ['nullable', 'integer', 'exists:markets,id']]);
        $markets = Market::where('is_active', true)
            ->when($validated['market'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->orderBy('name')->get();

        if ($markets->isEmpty()) {
            return to_route('price-import.create')->with('error', 'Pasar aktif untuk template tidak ditemukan.');
        }

        $latestDate = CommodityPrice::max('price_date');
        $nextDate = $latestDate
            ? CarbonImmutable::parse($latestDate)->addDay()->toDateString()
            : now()->addDay()->toDateString();
        $commodities = Commodity::with('category')->where('is_active', true)
            ->orderBy('category_id')->orderBy('name')->get();
        $rows = [];
        foreach ($markets as $market) {
            foreach ($commodities as $commodity) {
                $rows[] = [
                    'tanggal' => $nextDate,
                    'pasar' => $market->name,
                    'komoditas' => $commodity->name,
                    'satuan' => $commodity->unit,
                    'harga' => 0,
                ];
            }
        }

        $directory = storage_path('app/private/generated-templates');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $token = (string) str()->uuid();
        $jsonPath = $directory.DIRECTORY_SEPARATOR.$token.'.json';
        $xlsxPath = $directory.DIRECTORY_SEPARATOR.$token.'.xlsx';
        file_put_contents($jsonPath, json_encode([
            'rows' => $rows,
            'markets' => Market::where('is_active', true)->orderBy('name')->pluck('name')->all(),
            'commodities' => $commodities->map(fn ($item) => ['name' => $item->name, 'unit' => $item->unit])->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $process = new Process([(string) config('price-import.python_binary', 'python'), base_path('scripts/generate_price_template.py'), $jsonPath, $xlsxPath]);
        $process->setTimeout(120);
        $process->run();
        @unlink($jsonPath);
        if (! $process->isSuccessful() || ! is_file($xlsxPath)) {
            return to_route('price-import.create')->with('error', 'Template Excel gagal dibuat. '.trim($process->getErrorOutput()));
        }

        $scope = $markets->count() === 1 ? str($markets->first()->name)->slug('_') : 'semua_pasar';

        return response()->download($xlsxPath, "template_harga_{$scope}_{$nextDate}.xlsx")->deleteFileAfterSend(true);
    }

    public function preview(Request $request, PriceImportService $service): View|RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:25600']]);
        try {
            return view('master.prices.preview', ['import' => $service->stage($request->file('file'))]);
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function store(Request $request, PriceImportService $service, PredictionAutomationService $automation): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'uuid'],
            'duplicate_mode' => ['required', 'in:update,skip'],
        ]);
        try {
            $stats = $service->commit($validated['token'], $validated['duplicate_mode']);
        } catch (RuntimeException $exception) {
            return to_route('price-import.create')->with('error', $exception->getMessage());
        }

        $prediction = $automation->afterPriceImport($stats, $request->user()?->id);
        $message = number_format($stats['inserted']).' data baru ditambahkan, '.number_format($stats['updated']).' diperbarui, '.number_format($stats['skipped']).' dilewati, dan '.number_format($stats['invalid']).' tidak valid. '.$prediction['message'];

        return to_route('master-prices.index')->with('success', $message);
    }
}
