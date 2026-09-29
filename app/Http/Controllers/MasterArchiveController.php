<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\Infographic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MasterArchiveController extends Controller
{
    public function index()
    {
        return view('master.archive.index', ['categories' => Category::onlyTrashed()->latest('deleted_at')->get(), 'commodities' => Commodity::onlyTrashed()->with('category')->latest('deleted_at')->get(), 'infographics' => Infographic::onlyTrashed()->latest('deleted_at')->get()]);
    }

    public function restore(string $type, int $id)
    {
        $model = $this->model($type)::onlyTrashed()->findOrFail($id);
        $model->restore();

        return back()->with('success', 'Data berhasil dipulihkan dari arsip.');
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $model = $this->model($type)::onlyTrashed()->findOrFail($id);
        if ($model instanceof Infographic) {
            Storage::disk('public')->delete($model->image_path);
        }$model->forceDelete();

        return back()->with('success', 'Data dihapus permanen dari MySQL.');
    }

    private function model(string $type): string
    {
        return match ($type) {
            'kategori' => Category::class,'komoditas' => Commodity::class,'infografis' => Infographic::class,default => abort(404)
        };
    }
}
