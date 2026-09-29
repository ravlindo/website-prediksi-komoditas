<?php

namespace App\Console\Commands;

use App\Services\PredictionAutomationService;
use Illuminate\Console\Command;

class RunAutomaticPrediction extends Command
{
    protected $signature = 'predictions:auto {--date= : Tanggal batas data Y-m-d} {--sync : Jalankan sekarang tanpa antrean} {--force : Jalankan ulang walaupun tanggal yang sama sudah aktif}';

    protected $description = 'Menjalankan training, evaluasi, dan publikasi prediksi otomatis dari data MySQL';

    public function handle(PredictionAutomationService $service): int
    {
        $result = $service->start(
            date: $this->option('date') ?: null,
            force: (bool) $this->option('force'),
            sync: (bool) $this->option('sync'),
        );
        $this->line($result['message']);
        if (isset($result['run'])) {
            $this->line('Run: '.$result['run']->version.' · status '.$result['run']->status);
        }

        return in_array($result['status'], ['disabled', 'incomplete', 'error'], true) ? self::FAILURE : self::SUCCESS;
    }
}
