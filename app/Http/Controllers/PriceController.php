<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use App\Services\CommodityPriceService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PriceController extends Controller
{
    public function index(Request $request, CommodityPriceService $priceService): View
    {
        $date = $request->string('date')->toString() ?: (CommodityPrice::max('price_date') ?? now()->toDateString());
        $marketId = $request->integer('market') ?: null;
        $categoryId = $request->integer('category') ?: null;

        return view('prices.index', [
            'commodities' => $priceService->rows($date, $marketId, $categoryId),
            'categories' => Category::orderBy('name')->get(),
            'markets' => Market::where('is_active', true)->orderBy('name')->get(),
            'selectedDate' => $date,
            'selectedMarket' => $marketId,
            'selectedCategory' => $categoryId,
        ]);
    }

    public function show(Commodity $commodity, Request $request, CommodityPriceService $priceService): View
    {
        $date = $request->string('date')->toString()
            ?: (CommodityPrice::where('commodity_id', $commodity->id)->max('price_date') ?? now()->toDateString());
        $marketId = $request->integer('market') ?: null;
        $history = $priceService->history($commodity, 30, $marketId);
        $latest = $history->last();
        $previous = $history->slice(-2, 1)->first();

        return view('prices.show', [
            'commodity' => $commodity->load('category'),
            'history' => $history,
            'marketPrices' => $priceService->marketComparison($commodity, $date),
            'markets' => Market::where('is_active', true)->orderBy('name')->get(),
            'selectedMarket' => $marketId,
            'selectedDate' => $date,
            'latestPrice' => (int) ($latest->average_price ?? 0),
            'previousPrice' => (int) ($previous->average_price ?? $latest->average_price ?? 0),
            'minimumPrice' => (int) ($history->min('minimum_price') ?? 0),
            'maximumPrice' => (int) ($history->max('maximum_price') ?? 0),
            'averagePrice' => (int) round($history->avg('average_price') ?? 0),
        ]);
    }

    public function export(Request $request, CommodityPriceService $priceService): StreamedResponse
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'market' => ['nullable', 'integer', 'exists:markets,id'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
        ]);
        $date = $filters['date'] ?? (CommodityPrice::max('price_date') ?? now()->toDateString());
        $rows = $priceService->rows($date, $filters['market'] ?? null, $filters['category'] ?? null);
        $marketLabel = isset($filters['market']) ? (Market::find($filters['market'])?->name ?? 'Pasar tidak ditemukan') : 'Rata-rata Semua Pasar';

        return response()->streamDownload(function () use ($rows, $date, $marketLabel) {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fwrite($out, "sep=;\r\n");
            $this->writeCsvRow($out, ['No', 'Tanggal', 'Wilayah', 'Pasar', 'Kategori', 'Nama Bahan Pokok', 'Satuan', 'Harga Kemarin (Rp)', 'Harga Sekarang (Rp)', 'Perubahan (Rp)', 'Perubahan (%)', 'Status']);
            foreach ($rows as $index => $row) {
                $this->writeCsvRow($out, [$index + 1, date('d/m/Y', strtotime($date)), 'Kabupaten Mojokerto', $marketLabel, $row['category'], $row['name'], $row['unit'], $row['previous_value'], $row['current_value'], $row['change_value'], number_format($row['percentage_value'], 2, ',', ''), $row['label']]);
            }
            fclose($out);
        }, 'harga-komoditas-'.$date.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
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
