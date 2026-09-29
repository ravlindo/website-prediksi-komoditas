<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'market' => ['nullable', 'integer', 'exists:markets,id'], 'category' => ['nullable', 'integer', 'exists:categories,id'], 'commodity' => ['nullable', 'integer', 'exists:commodities,id']]);

        return view('reports.index', ['rows' => $this->query($filters)->paginate(40)->withQueryString(), 'filters' => $filters, 'markets' => Market::orderBy('name')->get(), 'categories' => Category::orderBy('name')->get(), 'commodities' => Commodity::where('is_active', true)->orderBy('name')->get(), 'summary' => (clone $this->query($filters))->reorder()->selectRaw('COUNT(*) total, COUNT(DISTINCT price_date) dates, AVG(price) average_price, SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) zero_count')->first()]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate(['start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'market' => ['nullable', 'integer', 'exists:markets,id'], 'category' => ['nullable', 'integer', 'exists:categories,id'], 'commodity' => ['nullable', 'integer', 'exists:commodities,id']]);
        if ($request->user()) {
            ActivityLog::create(['user_id' => $request->user()->id, 'user_name' => $request->user()->name, 'event' => 'report_export', 'route_name' => 'reports.export', 'method' => 'GET', 'path' => $request->path(), 'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'response_status' => 200, 'metadata' => ['filters' => $filters]]);
        }

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fwrite($out, "sep=;\r\n");
            $this->writeCsvRow($out, ['No', 'Tanggal', 'Wilayah', 'Pasar', 'Kategori', 'Komoditas', 'Satuan', 'Harga (Rp)', 'Sumber Data']);
            $number = 1;
            $this->query($filters)->chunk(1000, function ($rows) use ($out, &$number) {
                foreach ($rows as $row) {
                    $this->writeCsvRow($out, [$number++, $row->price_date->format('d/m/Y'), 'Kabupaten Mojokerto', $row->market->name, $row->commodity->category->name, $row->commodity->name, $row->commodity->unit, (int) $row->price, $row->source]);
                }
            });
            fclose($out);
        }, 'laporan-harga-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $filters = $request->validate(['start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'market' => ['nullable', 'integer', 'exists:markets,id'], 'category' => ['nullable', 'integer', 'exists:categories,id'], 'commodity' => ['nullable', 'integer', 'exists:commodities,id']]);
        $query = $this->query($filters);
        $total = (clone $query)->reorder()->count();
        if ($total > 5000) {
            return back()->with('error', 'PDF memuat '.number_format($total).' baris. Pilih periode, pasar, kategori, atau komoditas agar maksimal 5.000 baris per dokumen. CSV tetap dapat digunakan untuk data yang lebih besar.');
        }

        $rows = $query->get();
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml(view('reports.pdf', [
            'rows' => $rows, 'filters' => $filters, 'total' => $total,
            'marketName' => isset($filters['market']) ? Market::find($filters['market'])?->name : 'Semua pasar',
            'categoryName' => isset($filters['category']) ? Category::find($filters['category'])?->name : 'Semua kategori',
            'commodityName' => isset($filters['commodity']) ? Commodity::find($filters['commodity'])?->name : 'Semua komoditas',
        ])->render(), 'UTF-8');
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $dompdf->getCanvas()->page_text(725, 570, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 7, [0.35, 0.42, 0.52]);

        if ($request->user()) {
            ActivityLog::create(['user_id' => $request->user()->id, 'user_name' => $request->user()->name, 'event' => 'report_pdf', 'route_name' => 'reports.pdf', 'method' => 'GET', 'path' => $request->path(), 'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'response_status' => 200, 'metadata' => ['filters' => $filters, 'rows' => $total]]);
        }

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-harga-'.now()->format('Ymd-His').'.pdf"',
            'X-Report-Row-Count' => (string) $total,
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function query(array $filters)
    {
        return CommodityPrice::with(['commodity.category', 'market'])->when($filters['start_date'] ?? null, fn ($q, $v) => $q->whereDate('price_date', '>=', $v))->when($filters['end_date'] ?? null, fn ($q, $v) => $q->whereDate('price_date', '<=', $v))->when($filters['market'] ?? null, fn ($q, $v) => $q->where('market_id', $v))->when($filters['category'] ?? null, fn ($q, $v) => $q->whereHas('commodity', fn ($c) => $c->where('category_id', $v)))->when($filters['commodity'] ?? null, fn ($q, $v) => $q->where('commodity_id', $v))->orderByDesc('price_date')->orderBy('commodity_id')->orderBy('market_id');
    }

    private function writeCsvRow($handle, array $columns): void
    {
        fputcsv($handle, array_map(fn ($value) => $this->csvSafe($value), $columns), ';', '"', '');
    }

    private function csvSafe(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
