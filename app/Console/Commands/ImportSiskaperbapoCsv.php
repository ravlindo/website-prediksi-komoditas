<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\Market;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportSiskaperbapoCsv extends Command
{
    protected $signature = 'siskaperbapo:import-csv
        {directory : Folder berisi CSV hasil ekstraksi sheet SEMUA DATA}
        {--replace : Hapus seluruh data master dan harga sebelum impor}';

    protected $description = 'Impor data harga Siskaperbapo dari kumpulan CSV ke MySQL';

    public function handle(): int
    {
        $directory = realpath($this->argument('directory'));
        if (! $directory || ! is_dir($directory)) {
            $this->error('Folder CSV tidak ditemukan.');

            return self::FAILURE;
        }

        $files = glob($directory.DIRECTORY_SEPARATOR.'*.csv');
        if (! $files) {
            $this->error('Tidak ada file CSV di folder tersebut.');

            return self::FAILURE;
        }

        $expectedHeader = ['Tanggal', 'Kab/Kota', 'ID Pasar', 'Pasar', 'No Kategori', 'Kategori', 'Komoditas', 'Satuan', 'Harga Kemarin', 'Harga Sekarang', 'Perubahan (Rp)', 'Perubahan (%)'];
        $stats = ['read' => 0, 'inserted' => 0, 'zeros' => 0, 'invalid' => 0];

        DB::transaction(function () use ($files, $expectedHeader, &$stats) {
            if ($this->option('replace')) {
                DB::table('commodity_prices')->delete();
                DB::table('commodities')->delete();
                DB::table('categories')->delete();
                DB::table('markets')->delete();
            }

            $categoryIds = [];
            $commodityIds = [];
            $marketIds = [];
            $priceBatch = [];

            foreach ($files as $file) {
                $handle = fopen($file, 'rb');
                if (! $handle) {
                    throw new RuntimeException("Tidak dapat membaca {$file}");
                }

                $header = fgetcsv($handle);
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '');
                if ($header !== $expectedHeader) {
                    fclose($handle);
                    throw new RuntimeException('Header tidak sesuai pada '.basename($file));
                }

                while (($row = fgetcsv($handle)) !== false) {
                    $stats['read']++;
                    $row = array_pad($row, 12, '');
                    [$date, , $marketCode, $marketName, $categoryCode, $categoryName, $commodityName, $unit, , $currentPrice] = $row;

                    $price = filter_var($currentPrice, FILTER_VALIDATE_FLOAT);
                    if (! $date || ! $marketName || ! $categoryName || ! $commodityName || $price === false || $price < 0) {
                        $stats['invalid']++;

                        continue;
                    }
                    if ((float) $price === 0.0) {
                        $stats['zeros']++;
                    }

                    $categoryKey = mb_strtolower(trim($categoryName));
                    $categoryIds[$categoryKey] ??= Category::updateOrCreate(
                        ['slug' => Str::slug($categoryName)],
                        ['name' => Str::title(mb_strtolower(trim($categoryName)))],
                    )->id;

                    $marketKey = trim($marketCode) ?: mb_strtolower(trim($marketName));
                    $marketIds[$marketKey] ??= Market::updateOrCreate(
                        ['name' => trim($marketName)],
                        ['regency' => 'Kabupaten Mojokerto', 'is_active' => true],
                    )->id;

                    $commodityKey = $categoryIds[$categoryKey].'|'.mb_strtolower(trim($commodityName));
                    $commodityIds[$commodityKey] ??= Commodity::updateOrCreate(
                        ['slug' => Str::slug($commodityName)],
                        [
                            'category_id' => $categoryIds[$categoryKey],
                            'name' => trim($commodityName),
                            'unit' => trim($unit) ?: '-',
                            'is_active' => true,
                        ],
                    )->id;

                    $priceBatch[] = [
                        'commodity_id' => $commodityIds[$commodityKey],
                        'market_id' => $marketIds[$marketKey],
                        'price_date' => $date,
                        'price' => (int) round((float) $price),
                        'source' => 'Siskaperbapo Excel',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($priceBatch) >= 1000) {
                        $this->storePrices($priceBatch);
                        $stats['inserted'] += count($priceBatch);
                        $priceBatch = [];
                    }
                }
                fclose($handle);
                $this->line('Dibaca: '.basename($file));
            }

            if ($priceBatch) {
                $this->storePrices($priceBatch);
                $stats['inserted'] += count($priceBatch);
            }
        }, 3);

        $this->newLine();
        $this->table(['Metrik', 'Jumlah'], [
            ['Baris dibaca', number_format($stats['read'])],
            ['Baris disimpan', number_format($stats['inserted'])],
            ['Harga nol', number_format($stats['zeros'])],
            ['Baris tidak valid', number_format($stats['invalid'])],
        ]);

        return self::SUCCESS;
    }

    private function storePrices(array $rows): void
    {
        DB::table('commodity_prices')->upsert(
            $rows,
            ['commodity_id', 'market_id', 'price_date'],
            ['price', 'source', 'updated_at'],
        );
    }
}
