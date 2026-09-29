<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminInfographicController;
use App\Http\Controllers\AdminPredictionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommodityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataQualityController;
use App\Http\Controllers\InfographicController;
use App\Http\Controllers\MarketComparisonController;
use App\Http\Controllers\MasterArchiveController;
use App\Http\Controllers\MasterPriceController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\PriceImportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SynchronizationController;
use App\Http\Controllers\TrendController;
use Illuminate\Support\Facades\Route;

// Setiap menu memiliki route dan controller sendiri agar mudah dipelajari per fitur.
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/harga-komoditas', [PriceController::class, 'index'])->name('prices.index');
Route::get('/harga-komoditas/unduh/csv', [PriceController::class, 'export'])->name('prices.export');
Route::get('/harga-komoditas/{commodity:slug}', [PriceController::class, 'show'])->name('prices.show');
Route::get('/grafik-tren', [TrendController::class, 'index'])->name('trends.index');
Route::get('/infografis', [InfographicController::class, 'index'])->name('infographics.index');
Route::get('/perbandingan-pasar', [MarketComparisonController::class, 'index'])->name('markets.index');
Route::get('/prediksi-harga', [PredictionController::class, 'index'])->name('predictions.index');
Route::get('/prediksi-harga/evaluasi-pasar', [PredictionController::class, 'marketEvaluation'])->name('predictions.market-evaluation');
Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
Route::get('/laporan/ekspor', [ReportController::class, 'export'])->name('reports.export');
Route::get('/laporan/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

// Seluruh pengelolaan hanya dapat diakses oleh admin yang sudah login.
Route::middleware('auth')->group(function () {
    Route::get('/riwayat-aktivitas', [ActivityLogController::class, 'index'])->name('activity.index');
    Route::get('/backup-database', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backup-database', [BackupController::class, 'store'])->name('backups.store');
    Route::get('/backup-database/{file}', [BackupController::class, 'download'])->name('backups.download');
    Route::get('/arsip-master', [MasterArchiveController::class, 'index'])->name('master-archive.index');
    Route::patch('/arsip-master/{type}/{id}', [MasterArchiveController::class, 'restore'])->name('master-archive.restore');
    Route::delete('/arsip-master/{type}/{id}', [MasterArchiveController::class, 'destroy'])->name('master-archive.destroy');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/profil/password', [AuthController::class, 'editPassword'])->name('password.edit');
    Route::put('/profil/password', [AuthController::class, 'updatePassword'])->name('password.update');
    Route::get('/profil', [AuthController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profil', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::get('/sinkronisasi-data', [SynchronizationController::class, 'index'])->name('synchronization.index');
    Route::post('/sinkronisasi-data/periksa', [SynchronizationController::class, 'check'])->name('synchronization.check');
    Route::get('/kualitas-data', [DataQualityController::class, 'index'])->name('quality.index');

    Route::prefix('master')->group(function () {
        Route::get('prediksi', [AdminPredictionController::class, 'index'])->name('admin-predictions.index');
        Route::get('prediksi/impor', [AdminPredictionController::class, 'create'])->name('admin-predictions.create');
        Route::post('prediksi/impor', [AdminPredictionController::class, 'store'])->name('admin-predictions.store');
        Route::post('prediksi/otomatis', [AdminPredictionController::class, 'runAutomatic'])->name('admin-predictions.auto');
        Route::patch('prediksi/{predictionRun}/aktifkan', [AdminPredictionController::class, 'activate'])->name('admin-predictions.activate');
        Route::patch('infografis/{infographic}/status', [AdminInfographicController::class, 'toggleStatus'])->name('admin-infographics.status');
        Route::resource('infografis', AdminInfographicController::class)->except(['show'])->parameters(['infografis' => 'infographic'])->names('admin-infographics');
        Route::resource('kategori', CategoryController::class)->except(['show'])->names('categories');
        Route::resource('komoditas', CommodityController::class)->except(['show'])->names('commodities');
        Route::patch('komoditas/{commodity}/status', [CommodityController::class, 'toggleStatus'])->name('commodities.status');
        Route::delete('data-harga/bulk', [MasterPriceController::class, 'bulkDestroy'])->name('master-prices.bulk-destroy');
        Route::get('arsip-data-harga', [MasterPriceController::class, 'trash'])->name('master-prices.trash');
        Route::delete('arsip-data-harga/kosongkan', [MasterPriceController::class, 'purgeTrash'])->name('master-prices.purge-trash');
        Route::patch('arsip-data-harga/{id}/pulihkan', [MasterPriceController::class, 'restore'])->name('master-prices.restore');
        Route::delete('arsip-data-harga/{id}/permanen', [MasterPriceController::class, 'forceDestroy'])->name('master-prices.force-destroy');
        Route::patch('arsip-data-harga/pulihkan-massal', [MasterPriceController::class, 'bulkRestore'])->name('master-prices.bulk-restore');
        Route::delete('arsip-data-harga/hapus-massal', [MasterPriceController::class, 'bulkForceDestroy'])->name('master-prices.bulk-force-destroy');
        Route::resource('data-harga', MasterPriceController::class)->except(['show'])->parameters(['data-harga' => 'commodityPrice'])->names('master-prices');
        Route::get('impor-harga', [PriceImportController::class, 'create'])->name('price-import.create');
        Route::get('impor-harga/template', [PriceImportController::class, 'downloadTemplate'])->name('price-import.template');
        Route::post('impor-harga/preview', [PriceImportController::class, 'preview'])->name('price-import.preview');
        Route::post('impor-harga/commit', [PriceImportController::class, 'store'])->name('price-import.store');
    });
});
