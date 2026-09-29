<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ExportSeederDataset extends Command
{
    protected $signature = 'dataset:export-seeder';

    protected $description = 'Ekspor dataset aplikasi saat ini untuk digunakan oleh DatasetSeeder';

    /**
     * Data bisnis yang aman dibagikan. Akun, log, sesi, dan antrean sengaja tidak disertakan.
     *
     * @var list<string>
     */
    private const TABLES = [
        'categories',
        'markets',
        'commodities',
        'commodity_prices',
        'prediction_runs',
        'commodity_prediction_profiles',
        'commodity_predictions',
        'infographics',
    ];

    public function handle(): int
    {
        $directory = database_path('seeders/data');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Tidak dapat membuat direktori {$directory}.");
        }

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Tabel {$table} tidak ditemukan. Jalankan migration terlebih dahulu.");

                return self::FAILURE;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$table.'.jsonl.gz';
            $handle = gzopen($path, 'wb9');

            if ($handle === false) {
                throw new RuntimeException("Tidak dapat menulis {$path}.");
            }

            $count = 0;
            foreach (DB::table($table)->orderBy('id')->cursor() as $row) {
                $json = json_encode((array) $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                gzwrite($handle, $json."\n");
                $count++;
            }

            gzclose($handle);
            $this->line(sprintf('%-32s %s baris', $table, number_format($count, 0, ',', '.')));
        }

        $this->newLine();
        $this->info('Dataset seeder berhasil diekspor ke database/seeders/data.');

        return self::SUCCESS;
    }
}
