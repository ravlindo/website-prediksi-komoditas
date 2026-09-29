<?php

namespace App\Console\Commands;

use App\Services\PredictionImportService;
use Illuminate\Console\Command;

class ImportPredictions extends Command
{
    protected $signature = 'predictions:import-v3 {directory} {--model-version=V3_FINAL}';

    protected $description = 'Validasi, normalisasi, dan impor hasil prediksi V3 ke database';

    public function handle(PredictionImportService $service): int
    {
        try {
            $result = $service->import($this->argument('directory'), $this->option('model-version'));
            $this->info("Berhasil: {$result['profiles']} profil dan {$result['predictions']} prediksi.");
            $this->line('Status: '.json_encode($result['statuses'], JSON_UNESCAPED_UNICODE));
            $this->line('Berdasarkan data sampai: '.$result['data_last_date']);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
