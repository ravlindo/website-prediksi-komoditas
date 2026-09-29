<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database {--keep=7}';

    protected $description = 'Membuat backup SQL database dan mempertahankan beberapa file terakhir';

    public function handle(DatabaseBackupService $service): int
    {
        $dir = storage_path('app/private/backups');
        File::ensureDirectoryExists($dir);
        $path = $dir.'/mojokerto-harga-'.now()->format('Ymd-His').'.sql';
        $service->write($path);
        $files = collect(File::files($dir))->sortByDesc(fn ($f) => $f->getMTime())->values();
        $files->slice((int) $this->option('keep'))->each(fn ($f) => File::delete($f->getPathname()));
        $this->info($path);

        return self::SUCCESS;
    }
}
