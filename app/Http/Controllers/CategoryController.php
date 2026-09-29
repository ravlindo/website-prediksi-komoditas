<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->withCount('commodities')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('master.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('master.categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create(['name' => $request->string('name')->trim(), 'slug' => Str::slug($request->string('name'))]);

        return to_route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $kategori): View
    {
        return view('master.categories.form', ['category' => $kategori]);
    }

    public function update(CategoryRequest $request, Category $kategori): RedirectResponse
    {
        $kategori->update(['name' => $request->string('name')->trim(), 'slug' => Str::slug($request->string('name'))]);

        return to_route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $kategori): RedirectResponse
    {
        if ($kategori->commodities()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki komoditas.');
        }
        $kategori->delete();

        return to_route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
