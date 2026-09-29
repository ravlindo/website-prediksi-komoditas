<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DatabaseBackupService
{
    public function write(string $path): string
    {
        $pdo = DB::connection()->getPdo();
        $handle = fopen($path, 'wb');
        fwrite($handle, "-- Mojokerto Harga backup\n-- ".now()->toDateTimeString()." WIB\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $item) {
            $table = array_values((array) $item)[0];
            $safe = str_replace('`', '``', $table);
            $create = (array) DB::selectOne("SHOW CREATE TABLE `{$safe}`");
            $sql = array_values($create)[1];
            fwrite($handle, "DROP TABLE IF EXISTS `{$safe}`;\n{$sql};\n");
            DB::table($table)->orderBy(DB::raw('1'))->chunk(500, function ($rows) use ($handle, $pdo, $safe) {
                foreach ($rows as $row) {
                    $values = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values((array) $row));
                    fwrite($handle, "INSERT INTO `{$safe}` VALUES (".implode(',', $values).");\n");
                }
            });
            fwrite($handle, "\n");
        }
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return $path;
    }
}
