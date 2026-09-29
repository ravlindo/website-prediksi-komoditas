<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommodityRequest;
use App\Models\Category;
use App\Models\Commodity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CommodityController extends Controller
{
    public function index(Request $request): View
    {
        $commodities = Commodity::query()
            ->with('category')
            ->withCount('prices')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->integer('category'), fn ($query, $category) => $query->where('category_id', $category))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('master.commodities.index', [
            'commodities' => $commodities,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('master.commodities.form', ['commodity' => new Commodity, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(CommodityRequest $request): RedirectResponse
    {
        Commodity::create([
            ...$request->safe()->only(['category_id', 'name', 'unit']),
            'slug' => Str::slug($request->string('name')),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return to_route('commodities.index')->with('success', 'Komoditas berhasil ditambahkan.');
    }

    public function edit(Commodity $komodita): View
    {
        return view('master.commodities.form', ['commodity' => $komodita, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(CommodityRequest $request, Commodity $komodita): RedirectResponse
    {
        $komodita->update([
            ...$request->safe()->only(['category_id', 'name', 'unit']),
            'slug' => Str::slug($request->string('name')),
            'is_active' => $request->boolean('is_active'),
        ]);

        return to_route('commodities.index')->with('success', 'Komoditas berhasil diperbarui.');
    }

    public function toggleStatus(Commodity $commodity): RedirectResponse
    {
        $commodity->update(['is_active' => ! $commodity->is_active]);

        return back()->with('success', $commodity->is_active ? 'Komoditas diaktifkan.' : 'Komoditas dinonaktifkan.');
    }

    public function destroy(Commodity $komodita): RedirectResponse
    {
        $name = $komodita->name;
        $komodita->delete();

        return to_route('commodities.index')->with(
            'success',
            "Komoditas {$name} dipindahkan ke arsip. Riwayat harganya tetap aman."
        );
    }
}
