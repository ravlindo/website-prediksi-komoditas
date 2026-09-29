<?php

namespace App\Services;

use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class PriceImportService
{
    public function stage(UploadedFile $file): array
    {
        $token = (string) Str::uuid();
        $directory = storage_path("app/private/price-imports/{$token}");
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder penyimpanan impor tidak dapat dibuat.');
        }

        $safeName = 'sumber.'.strtolower($file->getClientOriginalExtension());
        $file->move($directory, $safeName);
        $sourcePath = $directory.DIRECTORY_SEPARATOR.$safeName;
        $csvPath = $this->toCsv($sourcePath, $directory);
        $result = $this->parse($csvPath);
        $result['token'] = $token;
        $result['file_name'] = $file->getClientOriginalName();
        file_put_contents($directory.DIRECTORY_SEPARATOR.'preview.json', json_encode($result, JSON_UNESCAPED_UNICODE));

        return $result;
    }

    public function load(string $token): array
    {
        $this->guardToken($token);
        $path = storage_path("app/private/price-imports/{$token}/preview.json");
        if (! is_file($path)) {
            throw new RuntimeException('Pratinjau impor sudah tidak tersedia. Silakan unggah kembali file.');
        }

        return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    public function commit(string $token, string $duplicateMode): array
    {
        $data = $this->load($token);
        $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'invalid' => $data['summary']['invalid'], 'changed_dates' => []];

        DB::transaction(function () use ($data, $duplicateMode, &$stats) {
            foreach (array_chunk($data['rows'], 750) as $chunk) {
                $write = [];
                foreach ($chunk as $row) {
                    if ($row['status'] === 'invalid') {
                        continue;
                    }
                    if ($row['status'] === 'duplicate' && $duplicateMode === 'skip') {
                        $stats['skipped']++;

                        continue;
                    }
                    $row['status'] === 'duplicate' ? $stats['updated']++ : $stats['inserted']++;
                    $stats['changed_dates'][$row['date']] = true;
                    $write[] = [
                        'commodity_id' => $row['commodity_id'], 'market_id' => $row['market_id'],
                        'price_date' => $row['date'], 'price' => $row['price'],
                        'source' => 'Import '.$data['file_name'], 'deleted_at' => null, 'deleted_by' => null,
                        'deleted_ip' => null, 'deletion_batch' => null, 'created_at' => now(), 'updated_at' => now(),
                    ];
                }
                if ($write) {
                    DB::table('commodity_prices')->upsert($write, ['commodity_id', 'market_id', 'price_date'], ['price', 'source', 'deleted_at', 'deleted_by', 'deleted_ip', 'deletion_batch', 'updated_at']);
                }
            }
        }, 3);

        @unlink(storage_path("app/private/price-imports/{$token}/preview.json"));
        $stats['changed_dates'] = array_keys($stats['changed_dates']);
        sort($stats['changed_dates']);

        return $stats;
    }

    private function toCsv(string $sourcePath, string $directory): string
    {
        if (strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) === 'csv') {
            return $sourcePath;
        }
        $python = (string) config('price-import.python_binary', 'python');
        $process = new Process([$python, base_path('scripts/excel_to_csv.py'), $sourcePath, '--output-dir', $directory]);
        $process->setTimeout(120);
        $process->run();
        if (! $process->isSuccessful()) {
            $detail = trim($process->getErrorOutput() ?: $process->getOutput());
            $detail = $detail ? Str::limit(preg_replace('/\s+/', ' ', $detail), 220) : 'Python tidak dapat dijalankan oleh XAMPP.';
            throw new RuntimeException('Excel tidak dapat dibaca: '.$detail);
        }
        $csv = $directory.DIRECTORY_SEPARATOR.pathinfo($sourcePath, PATHINFO_FILENAME).'.csv';
        if (! is_file($csv)) {
            throw new RuntimeException('Hasil pembacaan Excel tidak ditemukan.');
        }

        return $csv;
    }

    private function parse(string $csvPath): array
    {
        $handle = fopen($csvPath, 'rb');
        if (! $handle) {
            throw new RuntimeException('File tidak dapat dibaca.');
        }
        $header = fgetcsv($handle) ?: [];
        $header = array_map(fn ($value) => mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value))), $header);
        $indexes = $this->headerIndexes($header);

        $commodities = Commodity::all()->keyBy(fn ($item) => mb_strtolower(trim($item->name)));
        $markets = Market::all()->keyBy(fn ($item) => $this->marketKey($item->name));
        $existing = CommodityPrice::withTrashed()->select(['commodity_id', 'market_id', 'price_date'])->get()
            ->mapWithKeys(fn ($item) => [$item->commodity_id.'|'.$item->market_id.'|'.$item->price_date->format('Y-m-d') => true]);
        $rows = [];
        $seen = [];
        $line = 1;
        $summary = ['total' => 0, 'valid' => 0, 'duplicate' => 0, 'invalid' => 0, 'zeros' => 0];

        while (($raw = fgetcsv($handle)) !== false) {
            $line++;
            $summary['total']++;
            $name = trim((string) ($raw[$indexes['commodity']] ?? ''));
            $marketName = trim((string) ($raw[$indexes['market']] ?? ''));
            $date = $this->normalizeDate($raw[$indexes['date']] ?? '');
            $price = $this->normalizePrice($raw[$indexes['price']] ?? null);
            $commodity = $commodities->get(mb_strtolower($name));
            $market = $markets->get($this->marketKey($marketName));
            $errors = [];
            if (! $date) {
                $errors[] = 'Tanggal tidak valid';
            }
            if (! $commodity) {
                $errors[] = 'Komoditas tidak dikenali';
            }
            if (! $market) {
                $errors[] = 'Pasar tidak dikenali';
            }
            if ($price === null) {
                $errors[] = 'Harga tidak valid';
            }
            $key = $commodity && $market && $date ? $commodity->id.'|'.$market->id.'|'.$date : null;
            if ($key && isset($seen[$key])) {
                $errors[] = 'Duplikat di dalam file';
            }
            if ($key) {
                $seen[$key] = true;
            }
            $status = $errors ? 'invalid' : (isset($existing[$key]) ? 'duplicate' : 'valid');
            $summary[$status]++;
            if ($price === 0) {
                $summary['zeros']++;
            }
            $rows[] = [
                'line' => $line, 'date' => $date, 'commodity' => $name, 'market' => $marketName,
                'commodity_id' => $commodity?->id, 'market_id' => $market?->id, 'price' => $price,
                'unit' => $commodity?->unit ?? trim((string) ($raw[$indexes['unit']] ?? '')),
                'status' => $status, 'errors' => $errors,
            ];
        }
        fclose($handle);

        return ['summary' => $summary, 'rows' => $rows, 'preview_rows' => array_slice($rows, 0, 100)];
    }

    private function headerIndexes(array $header): array
    {
        $aliases = ['date' => ['tanggal', 'date'], 'market' => ['pasar', 'market'], 'commodity' => ['komoditas', 'nama komoditas', 'nama bahan pokok'], 'unit' => ['satuan', 'unit'], 'price' => ['harga sekarang', 'harga', 'price']];
        $result = [];
        foreach ($aliases as $key => $names) {
            foreach ($names as $name) {
                if (($index = array_search($name, $header, true)) !== false) {
                    $result[$key] = $index;
                    break;
                }
            }
            if (! isset($result[$key]) && $key !== 'unit') {
                throw new RuntimeException("Kolom {$names[0]} tidak ditemukan.");
            }
        }
        $result['unit'] ??= -1;

        return $result;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return CarbonImmutable::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    private function normalizePrice(mixed $value): ?int
    {
        $value = preg_replace('/[^0-9,.-]/', '', trim((string) $value));
        if ($value === '' || str_starts_with($value, '-')) {
            return null;
        }
        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $value)) {
            return (int) str_replace([',', '.'], '', $value);
        }
        if (preg_match('/^\d+[.,]\d{1,2}$/', $value)) {
            return (int) round((float) str_replace(',', '.', $value));
        }
        $normalized = preg_replace('/[^0-9]/', '', $value);

        return $normalized === '' ? null : (int) $normalized;
    }

    private function marketKey(string $value): string
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));

        return preg_replace('/^pasar\s+/', '', $normalized);
    }

    private function guardToken(string $token): void
    {
        if (! preg_match('/^[0-9a-f-]{36}$/i', $token)) {
            throw new RuntimeException('Token impor tidak valid.');
        }
    }
}
