<?php

namespace App\Http\Controllers;

use App\Models\PredictionRun;
use App\Services\PredictionAutomationService;
use App\Services\PredictionImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AdminPredictionController extends Controller
{
    public function index()
    {
        return view('master.predictions.index', ['runs' => PredictionRun::with(['creator', 'activator'])->latest()->paginate(15)]);
    }

    public function create()
    {
        return view('master.predictions.import');
    }

    public function store(Request $request, PredictionImportService $service)
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/'],
            'prediction_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'profile_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'evaluation_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);
        $directory = storage_path('app/private/prediction-imports/'.Str::uuid());
        File::ensureDirectoryExists($directory);
        $sourceName = $data['prediction_file']->getClientOriginalName();
        try {
            $data['prediction_file']->move($directory, 'prediksi_harga_komoditas_mojokerto_V3_FINAL_dengan_status.csv');
            $data['profile_file']->move($directory, 'quality_profile_komoditas_V3_FINAL.csv');
            if ($data['evaluation_file'] ?? null) {
                $data['evaluation_file']->move($directory, 'evaluasi_final_per_horizon_V3_FINAL.csv');
            }
            $result = $service->import($directory, $data['version'], $request->user()->id, $sourceName);
        } catch (\Throwable $error) {
            return back()->withInput()->with('error', 'Impor ditolak: '.$error->getMessage());
        } finally {
            File::deleteDirectory($directory);
        }

        return to_route('admin-predictions.index')->with('success', "Versi {$data['version']} aktif: {$result['predictions']} prediksi berhasil dinormalisasi.");
    }

    public function activate(Request $request, PredictionRun $predictionRun)
    {
        PredictionRun::where('status', 'active')->update(['status' => 'archived']);
        $predictionRun->update(['status' => 'active', 'activated_by' => $request->user()->id, 'activated_at' => now()]);

        return back()->with('success', "Model {$predictionRun->version} sekarang aktif.");
    }

    public function runAutomatic(Request $request, PredictionAutomationService $service)
    {
        $result = $service->start($request->user()?->id, force: true);
        $flash = in_array($result['status'], ['incomplete', 'disabled', 'error'], true) ? 'error' : 'success';

        return to_route('admin-predictions.index')->with($flash, $result['message']);
    }
}
