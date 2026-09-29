<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;

class DatasetSeeder extends Seeder
{
    /** @var list<string> */
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

    private const BATCH_SIZE = 1000;

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::TABLES as $table) {
                $this->seedTable($table);
            }
        }, 3);
    }

    private function seedTable(string $table): void
    {
        $path = database_path("seeders/data/{$table}.jsonl.gz");
        $handle = gzopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Dataset {$table} tidak ditemukan atau tidak dapat dibaca: {$path}");
        }

        $batch = [];
        $count = 0;

        try {
            while (! gzeof($handle)) {
                $line = gzgets($handle);
                if ($line === false || trim($line) === '') {
                    continue;
                }

                try {
                    $batch[] = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new RuntimeException("Dataset {$table} rusak di sekitar baris ".($count + 1).'.', 0, $exception);
                }

                if (count($batch) >= self::BATCH_SIZE) {
                    $count += $this->upsert($table, $batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $count += $this->upsert($table, $batch);
            }
        } finally {
            gzclose($handle);
        }

        $this->command?->line(sprintf('  %-32s %s baris', $table, number_format($count, 0, ',', '.')));
    }

    /** @param list<array<string, mixed>> $rows */
    private function upsert(string $table, array $rows): int
    {
        $columns = array_keys($rows[0]);
        $updateColumns = array_values(array_diff($columns, ['id']));

        DB::table($table)->upsert($rows, ['id'], $updateColumns);

        return count($rows);
    }
}
