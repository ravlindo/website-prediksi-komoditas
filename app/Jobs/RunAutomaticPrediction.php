<?php

namespace App\Jobs;

use App\Models\PredictionRun;
use App\Services\PredictionDatasetService;
use App\Services\PredictionImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class RunAutomaticPrediction implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 7200;

    public function __construct(public readonly int $predictionRunId) {}

    public function handle(PredictionDatasetService $datasets, PredictionImportService $importer): void
    {
        $started = microtime(true);
        $run = PredictionRun::findOrFail($this->predictionRunId);
        if (! in_array($run->status, ['queued', 'processing'], true)) {
            return;
        }

        $directory = storage_path("app/private/prediction-runs/{$run->id}");
        File::ensureDirectoryExists($directory);
        $datasetPath = $directory.DIRECTORY_SEPARATOR.'dataset_harga.csv';
        $published = false;

        try {
            $this->stage($run, 5, 'Menyiapkan dataset dari MySQL', ['status' => 'processing', 'started_at' => now(), 'error_message' => null]);
            $export = $datasets->export($run->data_last_date->toImmutable(), $datasetPath);
            $this->stage($run, 12, 'Mengevaluasi sembilan kandidat model', [
                'notes' => "Dataset otomatis berisi {$export['rows']} baris; {$export['actual']}/{$export['expected']} data pada tanggal terakhir.",
                'source_file' => 'prediction-runs/'.$run->id.'/dataset_harga.csv',
            ]);

            $process = new Process([
                (string) config('prediction-automation.python_binary', 'python'),
                base_path('scripts/prediction_pipeline.py'),
                '--input', $datasetPath,
                '--output', $directory,
                '--cutoff', $run->data_last_date->toDateString(),
                '--version', $run->version,
                '--min-history', (string) config('prediction-automation.minimum_history', 120),
                '--min-test', (string) config('prediction-automation.minimum_test_observations', 14),
                '--max-missing', (string) config('prediction-automation.maximum_missing_rate', 0.70),
                '--max-mape', (string) config('prediction-automation.maximum_mape', 15),
            ]);
            $process->setTimeout((float) config('prediction-automation.timeout_seconds', 7200));
            $process->run();

            if (! $process->isSuccessful()) {
                $detail = trim($process->getErrorOutput() ?: $process->getOutput());
                throw new RuntimeException('Mesin Python gagal: '.str($detail)->squish()->limit(1200));
            }

            $summaryPath = $directory.DIRECTORY_SEPARATOR.'run_summary.json';
            if (! is_file($summaryPath)) {
                throw new RuntimeException('Ringkasan hasil mesin prediksi tidak ditemukan.');
            }
            $summary = json_decode(file_get_contents($summaryPath), true, flags: JSON_THROW_ON_ERROR);
            if (($summary['prediction_count'] ?? 0) < 1) {
                throw new RuntimeException('Mesin tidak menghasilkan prediksi yang dapat diaudit.');
            }

            $this->stage($run, 90, 'Memvalidasi dan menyimpan hasil ke MySQL');
            $result = $importer->import(
                $directory,
                $run->version,
                $run->created_by,
                'Otomatis · dataset sampai '.$run->data_last_date->toDateString(),
                $run->data_last_date->toDateString(),
            );
            $published = true;

            try {
                $run->refresh()->update([
                    'progress' => 100,
                    'current_stage' => 'Selesai dan dipublikasikan',
                    'finished_at' => now(),
                    'runtime_seconds' => (int) round(microtime(true) - $started),
                    'notes' => "{$result['profiles']} profil dan {$result['predictions']} prediksi tersimpan. Output audit: prediction-runs/{$run->id}.",
                ]);
            } catch (Throwable $metadataError) {
                // Hasil sudah aktif secara transaksional; kegagalan metadata tidak boleh membatalkan publikasi yang valid.
                report($metadataError);
            }
        } catch (Throwable $error) {
            if ($published) {
                report($error);

                return;
            }
            $run->refresh()->update([
                'status' => 'failed',
                'progress' => 100,
                'current_stage' => 'Proses gagal; prediksi aktif sebelumnya dipertahankan',
                'error_message' => str($error->getMessage())->limit(3000),
                'finished_at' => now(),
                'runtime_seconds' => (int) round(microtime(true) - $started),
            ]);
            throw $error;
        }
    }

    public function failed(?Throwable $error): void
    {
        PredictionRun::whereKey($this->predictionRunId)->whereNotIn('status', ['active', 'failed'])->update([
            'status' => 'failed',
            'progress' => 100,
            'current_stage' => 'Job prediksi dihentikan',
            'error_message' => $error ? str($error->getMessage())->limit(3000) : 'Job dihentikan tanpa detail error.',
            'finished_at' => now(),
        ]);
    }

    private function stage(PredictionRun $run, int $progress, string $stage, array $extra = []): void
    {
        $run->update(['progress' => $progress, 'current_stage' => $stage, ...$extra]);
    }
}
