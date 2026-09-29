<?php

namespace App\Services;

use App\Models\Commodity;
use App\Models\CommodityPrediction;
use App\Models\CommodityPredictionProfile;
use App\Models\CommodityPrice;
use App\Models\PredictionRun;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PredictionImportService
{
    public const VERSION = 'V3_FINAL';

    public function normalize(string $originalStatus, string $seriesType, ?float $mape, ?float $mae = null, ?float $naiveMae = null, ?bool $beatsNaive = null): array
    {
        if (in_array($seriesType, ['TIDAK ADA DATA VALID', 'DATA TIDAK MENCUKUPI', 'MISSING TERLALU TINGGI'], true)) {
            return ['status' => 'DATA TIDAK CUKUP', 'reason' => 'Tidak tersedia riwayat harga valid untuk membentuk prediksi.'];
        }
        if (in_array($seriesType, ['KONSTAN', 'HAMPIR KONSTAN'], true)) {
            return ['status' => 'STABIL', 'reason' => 'Pola historis konstan atau hampir konstan; hasil ditampilkan sebagai proyeksi stabil.'];
        }
        if ($originalStatus !== 'LAYAK') {
            $detail = $mae !== null && $naiveMae !== null
                ? sprintf('MAPE %.2f%% %s batas 15%%, tetapi MAE model Rp%s lebih buruk daripada Naive Rp%s.', $mape ?? 0, ($mape ?? 999) <= 15 ? 'memenuhi' : 'melewati', number_format($mae, 0, ',', '.'), number_format($naiveMae, 0, ',', '.'))
                : 'Model belum mengungguli acuan sederhana Naive pada pengujian.';

            return ['status' => 'BELUM LAYAK', 'reason' => $detail];
        }
        if ($mape === null || $mape > 15) {
            return ['status' => 'BELUM LAYAK', 'reason' => 'MAPE melebihi batas kelayakan 15%.'];
        }
        if ($beatsNaive === false) {
            return [
                'status' => 'BELUM LAYAK',
                'reason' => $mae !== null && $naiveMae !== null
                    ? sprintf('MAPE %.2f%% memenuhi batas, tetapi MAE model Rp%s belum mengungguli Naive Rp%s.', $mape, number_format($mae, 0, ',', '.'), number_format($naiveMae, 0, ',', '.'))
                    : 'Model belum mengungguli acuan sederhana Naive pada pengujian.',
            ];
        }

        return ['status' => 'LAYAK', 'reason' => 'Lolos evaluasi dan MAPE tidak melebihi 15%.'];
    }

    public function normalizeInterval(float $prediction, float $lower, float $upper): array
    {
        $normalizedLower = min($lower, $prediction);
        $normalizedUpper = max($upper, $prediction);

        return [$normalizedLower, $normalizedUpper, $normalizedLower !== $lower || $normalizedUpper !== $upper];
    }

    public function accuracy(?float $mape): ?float
    {
        return $mape === null ? null : round(max(0, min(100, 100 - $mape)), 2);
    }

    public function import(string $directory, string $version = self::VERSION, ?int $userId = null, ?string $sourceFile = null, ?string $expectedDataLastDate = null): array
    {
        $predictionFile = $directory.DIRECTORY_SEPARATOR.'prediksi_harga_komoditas_mojokerto_V3_FINAL_dengan_status.csv';
        $profileFile = $directory.DIRECTORY_SEPARATOR.'quality_profile_komoditas_V3_FINAL.csv';
        $evaluationFile = $directory.DIRECTORY_SEPARATOR.'evaluasi_final_per_horizon_V3_FINAL.csv';
        if (! is_file($predictionFile) || ! is_file($profileFile)) {
            throw new RuntimeException('File prediksi dengan status atau quality profile tidak ditemukan.');
        }

        $commodities = Commodity::all()->keyBy(fn ($item) => $this->key($item->name));
        $profiles = collect($this->csv($profileFile));
        $predictions = collect($this->csv($predictionFile));
        $evaluations = is_file($evaluationFile) ? collect($this->csv($evaluationFile))->keyBy(fn ($row) => $this->key($row['komoditas']).'|'.(int) $row['horizon_hari']) : collect();
        $lastDate = $expectedDataLastDate ?: CommodityPrice::max('price_date');
        if (! $lastDate) {
            throw new RuntimeException('Tanggal data harga terakhir tidak ditemukan.');
        }
        if ($profiles->isEmpty() || $predictions->isEmpty()) {
            throw new RuntimeException('Output model kosong; versi sebelumnya tetap dipertahankan.');
        }
        $duplicatePredictions = $predictions->groupBy(fn ($row) => $this->key($row['komoditas']).'|'.(int) $row['horizon_hari'])->filter(fn ($rows) => $rows->count() > 1);
        if ($duplicatePredictions->isNotEmpty()) {
            throw new RuntimeException('Output memiliki prediksi ganda untuk komoditas dan horizon yang sama.');
        }
        $unknown = $profiles->pluck('komoditas')->filter(fn ($name) => ! $commodities->has($this->key($name)))->values();
        if ($unknown->isNotEmpty()) {
            throw new RuntimeException('Komoditas tidak cocok dengan database: '.$unknown->implode(', '));
        }
        $profileByName = $profiles->keyBy(fn ($row) => $this->key($row['komoditas']));

        return DB::transaction(function () use ($profiles, $predictions, $evaluations, $commodities, $profileByName, $lastDate, $version, $userId, $sourceFile) {
            CommodityPrediction::where('model_version', $version)->delete();
            $run = PredictionRun::updateOrCreate(['version' => $version], [
                'status' => 'active', 'data_last_date' => $lastDate, 'source_file' => $sourceFile,
                'created_by' => $userId, 'activated_by' => $userId, 'activated_at' => now(),
            ]);
            PredictionRun::whereKeyNot($run->id)->where('status', 'active')->update(['status' => 'archived']);
            $statusCounts = [];
            foreach ($profiles as $row) {
                $commodity = $commodities[$this->key($row['komoditas'])];
                $seriesType = strtoupper(trim($row['tipe_seri']));
                CommodityPredictionProfile::updateOrCreate(['commodity_id' => $commodity->id, 'prediction_run_id' => $run->id], [
                    'valid_price_count' => (int) $row['harga_valid'],
                    'missing_rate' => (float) $row['missing_rate'],
                    'unique_price_count' => (int) $row['unique_price'],
                    'coefficient_variation' => $row['cv'] === '' ? null : (float) $row['cv'],
                    'series_type' => $seriesType,
                    'availability_status' => $seriesType === 'TIDAK ADA DATA VALID' ? 'DATA TIDAK CUKUP' : (in_array($seriesType, ['KONSTAN', 'HAMPIR KONSTAN'], true) ? 'STABIL' : 'TERSEDIA'),
                    'model_version' => $version,
                    'data_last_date' => $lastDate,
                ]);
            }

            foreach ($predictions as $row) {
                $key = $this->key($row['komoditas']);
                if (! $commodities->has($key) || ! $profileByName->has($key)) {
                    throw new RuntimeException('Baris prediksi tidak memiliki pasangan komoditas/profile: '.$row['komoditas']);
                }
                $horizon = (int) $row['horizon_hari'];
                if (! in_array($horizon, [1, 3, 7, 14, 30], true)) {
                    throw new RuntimeException('Horizon prediksi tidak didukung: '.$horizon.' hari.');
                }
                $expectedTarget = CarbonImmutable::parse($lastDate)->addDays($horizon)->toDateString();
                if (CarbonImmutable::parse($row['tanggal_prediksi'])->toDateString() !== $expectedTarget) {
                    throw new RuntimeException("Tanggal target {$row['komoditas']} horizon {$horizon} harus {$expectedTarget}.");
                }
                foreach (['harga_prediksi', 'batas_bawah', 'batas_atas'] as $numericColumn) {
                    if (! is_numeric($row[$numericColumn] ?? null) || ! is_finite((float) $row[$numericColumn]) || (float) $row[$numericColumn] < 0) {
                        throw new RuntimeException("Nilai {$numericColumn} tidak valid untuk {$row['komoditas']} horizon {$horizon}.");
                    }
                }
                $mape = $row['mape'] === '' ? null : (float) $row['mape'];
                $evaluation = $evaluations->get($key.'|'.$horizon);
                $mae = $row['mae'] === '' ? null : (float) $row['mae'];
                $naiveMae = isset($evaluation['naive_test_mae']) && $evaluation['naive_test_mae'] !== '' ? (float) $evaluation['naive_test_mae'] : null;
                $beatsNaive = isset($evaluation['model_validation_lolos_test_vs_naive']) ? filter_var($evaluation['model_validation_lolos_test_vs_naive'], FILTER_VALIDATE_BOOLEAN) : null;
                $normalized = $this->normalize(strtoupper(trim($row['status_layak'])), strtoupper(trim($profileByName[$key]['tipe_seri'])), $mape, $mae, $naiveMae, $beatsNaive);
                [$lower, $upper, $adjusted] = $this->normalizeInterval((float) $row['harga_prediksi'], (float) $row['batas_bawah'], (float) $row['batas_atas']);
                $statusCounts[$normalized['status']] = ($statusCounts[$normalized['status']] ?? 0) + 1;
                CommodityPrediction::create([
                    'prediction_run_id' => $run->id,
                    'commodity_id' => $commodities[$key]->id,
                    'target_date' => $row['tanggal_prediksi'],
                    'horizon_days' => $horizon,
                    'predicted_price' => (float) $row['harga_prediksi'],
                    'lower_bound' => $lower,
                    'upper_bound' => $upper,
                    'model_name' => $row['nama_model'],
                    'mae' => $mae,
                    'naive_mae' => $naiveMae,
                    'beats_naive' => $beatsNaive,
                    'rmse' => $row['rmse'] === '' ? null : (float) $row['rmse'],
                    'mape' => $mape,
                    'smape' => $row['smape'] === '' ? null : (float) $row['smape'],
                    'accuracy_score' => $this->accuracy($mape),
                    'original_status' => strtoupper(trim($row['status_layak'])),
                    'normalized_status' => $normalized['status'],
                    'status_reason' => $normalized['reason'],
                    'interval_adjusted' => $adjusted,
                    'model_version' => $version,
                    'data_last_date' => $lastDate,
                    'model_generated_at' => $row['tanggal_model_dibuat'] ?: null,
                ]);
            }
            $run->update([
                'profile_count' => $profiles->count(), 'prediction_count' => $predictions->count(),
                'status_summary' => $statusCounts,
                'generated_at' => $predictions->pluck('tanggal_model_dibuat')->filter()->first(),
                'status' => 'active',
            ]);

            return ['profiles' => $profiles->count(), 'predictions' => $predictions->count(), 'statuses' => $statusCounts, 'data_last_date' => (string) $lastDate];
        });
    }

    private function key(string $value): string
    {
        return Str::lower(preg_replace('/\s+/', ' ', trim($value)));
    }

    private function csv(string $path): array
    {
        $handle = fopen($path, 'rb');
        $headers = array_map(fn ($header) => trim($header, "\xEF\xBB\xBF \t\n\r\0\x0B"), fgetcsv($handle));
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count($values) === count($headers) && collect($values)->filter(fn ($value) => trim((string) $value) !== '')->isNotEmpty()) {
                $rows[] = array_combine($headers, $values);
            }
        }
        fclose($handle);

        return $rows;
    }
}
