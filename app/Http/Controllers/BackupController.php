<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    public function index()
    {
        $dir = storage_path('app/private/backups');
        File::ensureDirectoryExists($dir);

        return view('master.backups.index', ['files' => collect(File::files($dir))->sortByDesc(fn ($f) => $f->getMTime())]);
    }

    public function store(DatabaseBackupService $service)
    {
        $dir = storage_path('app/private/backups');
        File::ensureDirectoryExists($dir);
        $service->write($dir.'/mojokerto-harga-'.now()->format('Ymd-His').'.sql');

        return back()->with('success', 'Backup database berhasil dibuat.');
    }

    public function download(string $file)
    {
        abort_unless(preg_match('/^mojokerto-harga-\d{8}-\d{6}\.sql$/', $file), 404);
        $path = storage_path('app/private/backups/'.$file);
        abort_unless(is_file($path), 404);

        return response()->download($path);
    }
}
