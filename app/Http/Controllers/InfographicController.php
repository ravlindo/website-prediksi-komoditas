<?php

namespace App\Http\Controllers;

use App\Models\Infographic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InfographicController extends Controller
{
    public function index(Request $request): View
    {
        $years = Infographic::published()->whereNotNull('publication_year')->distinct()->orderByDesc('publication_year')->pluck('publication_year');
        $infographics = Infographic::query()
            ->published()
            ->when($request->filled('year'), fn ($query) => $query->where('publication_year', $request->integer('year')))
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(function ($nested) use ($search) {
                $nested->where('title', 'like', "%{$search}%")->orWhere('agency', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')->latest('publication_year')->latest('id')->paginate(12)->withQueryString();

        return view('infographics.index', compact('infographics', 'years'));
    }
}
