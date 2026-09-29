<?php

namespace App\Services;

use App\Models\Commodity;
use App\Models\Market;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class PredictionDatasetService
{
    public function expectedRowsPerDate(): int
    {
        return Commodity::where('is_active', true)->count() * Market::where('is_active', true)->count();
    }

    public function latestCompleteDate(): ?CarbonImmutable
    {
        $expected = $this->expectedRowsPerDate();
        if ($expected === 0) {
            return null;
        }

        $date = DB::table('commodity_prices as cp')
            ->join('commodities as c', 'c.id', '=', 'cp.commodity_id')
            ->join('markets as m', 'm.id', '=', 'cp.market_id')
            ->whereNull('cp.deleted_at')
            ->where('c.is_active', true)
            ->where('m.is_active', true)
            ->groupBy('cp.price_date')
            ->havingRaw('COUNT(*) = ?', [$expected])
            ->orderByDesc('cp.price_date')
            ->value('cp.price_date');

        return $date ? CarbonImmutable::parse($date) : null;
    }

    public function readiness(string|CarbonImmutable|null $date = null): array
    {
        $date = $date ? CarbonImmutable::parse($date) : $this->latestCompleteDate();
        $expected = $this->expectedRowsPerDate();
        $actual = $date ? DB::table('commodity_prices as cp')
            ->join('commodities as c', 'c.id', '=', 'cp.commodity_id')
            ->join('markets as m', 'm.id', '=', 'cp.market_id')
            ->whereNull('cp.deleted_at')
            ->where('c.is_active', true)
            ->where('m.is_active', true)
            ->whereDate('cp.price_date', $date->toDateString())
            ->count() : 0;

        return [
            'date' => $date?->toDateString(),
            'expected' => $expected,
            'actual' => $actual,
            'complete' => $date !== null && $expected > 0 && $actual === $expected,
            'markets' => Market::where('is_active', true)->count(),
            'commodities' => Commodity::where('is_active', true)->count(),
        ];
    }

    public function export(CarbonImmutable $cutoff, string $path): array
    {
        $readiness = $this->readiness($cutoff);
        if (! $readiness['complete']) {
            throw new RuntimeException("Data {$cutoff->toDateString()} belum lengkap: {$readiness['actual']} dari {$readiness['expected']} baris.");
        }

        File::ensureDirectoryExists(dirname($path));
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Dataset sementara untuk prediksi tidak dapat dibuat.');
        }

        fputcsv($handle, ['tanggal', 'pasar', 'kategori', 'komoditas', 'satuan', 'harga']);
        $count = 0;
        DB::table('commodity_prices as cp')
            ->join('commodities as c', 'c.id', '=', 'cp.commodity_id')
            ->join('categories as cat', 'cat.id', '=', 'c.category_id')
            ->join('markets as m', 'm.id', '=', 'cp.market_id')
            ->whereNull('cp.deleted_at')
            ->where('c.is_active', true)
            ->where('m.is_active', true)
            ->whereDate('cp.price_date', '<=', $cutoff->toDateString())
            ->orderBy('cp.price_date')->orderBy('m.id')->orderBy('c.id')
            ->select(['cp.price_date', 'm.name as market_name', 'cat.name as category_name', 'c.name as commodity_name', 'c.unit', 'cp.price'])
            ->chunk(2000, function ($rows) use ($handle, &$count) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->price_date,
                        $row->market_name,
                        $row->category_name,
                        $row->commodity_name,
                        $row->unit,
                        $row->price,
                    ]);
                    $count++;
                }
            });
        fclose($handle);

        if ($count === 0) {
            @unlink($path);
            throw new RuntimeException('Riwayat harga untuk pelatihan model tidak ditemukan.');
        }

        return ['rows' => $count, ...$readiness];
    }
}
