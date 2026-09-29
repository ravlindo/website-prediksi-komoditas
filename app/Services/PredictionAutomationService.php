<?php

namespace App\Services;

use App\Jobs\RunAutomaticPrediction;
use App\Models\PredictionRun;
use Carbon\CarbonImmutable;

class PredictionAutomationService
{
    public function __construct(private readonly PredictionDatasetService $datasets) {}

    public function start(?int $userId = null, ?string $date = null, bool $force = false, bool $sync = false): array
    {
        if (! config('prediction-automation.enabled')) {
            return ['status' => 'disabled', 'message' => 'Prediksi otomatis dinonaktifkan melalui konfigurasi.'];
        }

        $cutoff = $date ? CarbonImmutable::parse($date) : $this->datasets->latestCompleteDate();
        if (! $cutoff) {
            return ['status' => 'incomplete', 'message' => 'Belum ada tanggal dengan data empat pasar dan seluruh komoditas yang lengkap.'];
        }

        $readiness = $this->datasets->readiness($cutoff);
        if (! $readiness['complete']) {
            return [
                'status' => 'incomplete',
                'message' => "Data {$cutoff->translatedFormat('d F Y')} baru {$readiness['actual']} dari {$readiness['expected']} baris.",
                'readiness' => $readiness,
            ];
        }

        $running = PredictionRun::whereDate('data_last_date', $cutoff->toDateString())
            ->whereIn('status', ['queued', 'processing'])
            ->latest()->first();
        if ($running) {
            return ['status' => 'already_running', 'run' => $running, 'message' => "Prediksi {$cutoff->translatedFormat('d F Y')} sudah berada dalam antrean."];
        }

        if (! $force) {
            $active = PredictionRun::whereDate('data_last_date', $cutoff->toDateString())->where('status', 'active')->latest()->first();
            if ($active) {
                return ['status' => 'up_to_date', 'run' => $active, 'message' => "Prediksi aktif sudah memakai data sampai {$cutoff->translatedFormat('d F Y')}."];
            }
        }

        $run = PredictionRun::create([
            'version' => 'AUTO_V3_'.$cutoff->format('Ymd').'_'.now()->format('YmdHis').'_'.str()->lower(str()->random(6)),
            'status' => 'queued',
            'trigger_type' => $sync ? 'manual' : 'automatic',
            'progress' => 0,
            'current_stage' => 'Menunggu worker prediksi',
            'data_last_date' => $cutoff->toDateString(),
            'queued_at' => now(),
            'created_by' => $userId,
            'notes' => "Dijadwalkan dari {$readiness['actual']} baris pada tanggal batas data.",
        ]);

        if ($sync) {
            RunAutomaticPrediction::dispatchSync($run->id);
            $run->refresh();
        } else {
            RunAutomaticPrediction::dispatch($run->id)->onQueue((string) config('prediction-automation.queue', 'predictions'));
        }

        return ['status' => $run->status, 'run' => $run, 'message' => 'Proses prediksi otomatis berhasil dijadwalkan.'];
    }

    public function afterPriceImport(array $stats, ?int $userId = null): array
    {
        if (! config('prediction-automation.after_price_import')) {
            return ['status' => 'disabled', 'message' => 'Pemicu setelah impor dinonaktifkan.'];
        }
        if (($stats['inserted'] ?? 0) + ($stats['updated'] ?? 0) === 0) {
            return ['status' => 'unchanged', 'message' => 'Tidak ada perubahan harga yang memerlukan prediksi ulang.'];
        }

        $dates = collect($stats['changed_dates'] ?? [])->filter()->sortDesc();
        $completeDate = $dates->first(fn ($date) => $this->datasets->readiness($date)['complete']);
        if (! $completeDate) {
            return [
                'status' => 'waiting_for_complete_data',
                'message' => 'Harga tersimpan. Prediksi menunggu seluruh pasar dan komoditas pada tanggal baru lengkap.',
            ];
        }

        try {
            return $this->start($userId, (string) $completeDate, force: true);
        } catch (\Throwable $error) {
            report($error);

            return ['status' => 'error', 'message' => 'Data berhasil disimpan, tetapi penjadwalan prediksi gagal: '.$error->getMessage()];
        }
    }
}
