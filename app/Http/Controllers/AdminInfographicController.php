<?php

namespace App\Http\Controllers;

use App\Http\Requests\InfographicRequest;
use App\Models\Infographic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminInfographicController extends Controller
{
    public function index(Request $request): View
    {
        $infographics = Infographic::query()->with('editor')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($request->filled('status'), fn ($query) => $query->where('is_published', $request->string('status')->toString() === 'published'))
            ->orderBy('sort_order')->latest('id')->paginate(15)->withQueryString();

        return view('master.infographics.index', compact('infographics'));
    }

    public function create(): View
    {
        return view('master.infographics.form', ['infographic' => new Infographic]);
    }

    public function store(InfographicRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['slug'] = $this->uniqueSlug($request->string('title'));
        $data['image_path'] = $request->file('image')->store('infographics', 'public');
        $data['is_published'] = $request->boolean('is_published');
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        Infographic::create($data);

        return to_route('admin-infographics.index')->with('success', 'Infografis berhasil ditambahkan dan siap ditampilkan.');
    }

    public function edit(Infographic $infographic): View
    {
        return view('master.infographics.form', compact('infographic'));
    }

    public function update(InfographicRequest $request, Infographic $infographic): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['slug'] = $this->uniqueSlug($request->string('title'), $infographic->id);
        $data['is_published'] = $request->boolean('is_published');
        $data['updated_by'] = $request->user()->id;

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('infographics', 'public');
            Storage::disk('public')->delete($infographic->image_path);
            $data['image_path'] = $newPath;
        }

        $infographic->update($data);

        return to_route('admin-infographics.index')->with('success', 'Infografis berhasil diperbarui.');
    }

    public function destroy(Infographic $infographic): RedirectResponse
    {
        $infographic->delete();

        return to_route('admin-infographics.index')->with('success', 'Infografis dipindahkan ke arsip dan gambarnya tetap aman.');
    }

    public function toggleStatus(Request $request, Infographic $infographic): RedirectResponse
    {
        $infographic->update(['is_published' => ! $infographic->is_published, 'updated_by' => $request->user()->id]);

        return back()->with('success', $infographic->is_published ? 'Infografis berhasil dipublikasikan.' : 'Infografis disembunyikan dari halaman publik.');
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'infografis';
        $slug = $base;
        $number = 2;
        while (Infographic::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$number++;
        }

        return $slug;
    }
}
