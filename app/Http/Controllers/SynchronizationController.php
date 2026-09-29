<?php

namespace App\Http\Controllers;

use App\Models\SynchronizationRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class SynchronizationController extends Controller
{
    public function index(): View
    {
        return view('synchronization.index', ['runs' => SynchronizationRun::with('creator')->latest()->paginate(20), 'endpoint' => config('services.siskaperbapo.sync_url')]);
    }

    public function check(Request $request): RedirectResponse
    {
        $run = SynchronizationRun::create(['status' => 'running', 'trigger' => 'manual', 'started_at' => now(), 'created_by' => $request->user()->id]);
        $url = config('services.siskaperbapo.sync_url');
        if (! $url) {
            $run->update(['status' => 'blocked', 'finished_at' => now(), 'message' => 'Endpoint belum dikonfigurasi. Tambahkan SISKAPERBAPO_SYNC_URL pada .env.']);

            return back()->with('error', $run->message);
        }
        try {
            $response = Http::timeout(20)->acceptJson()->get($url);
            $ok = $response->successful();
            $run->update(['status' => $ok ? 'connected' : 'failed', 'finished_at' => now(), 'message' => $ok ? 'Endpoint dapat dihubungi. Data belum ditulis sebelum pemetaan respons disetujui.' : 'HTTP '.$response->status()]);

            return back()->with($ok ? 'success' : 'error', $run->message);
        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'finished_at' => now(), 'message' => mb_substr($e->getMessage(), 0, 1000)]);

            return back()->with('error', 'Koneksi gagal: '.$e->getMessage());
        }
    }
}
