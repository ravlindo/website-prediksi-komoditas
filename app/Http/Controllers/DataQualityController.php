<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use App\Services\DataQualityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataQualityController extends Controller
{
    public function index(Request $request, DataQualityService $qualityService): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'market' => ['nullable', 'integer', 'exists:markets,id'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'commodity' => ['nullable', 'integer', 'exists:commodities,id'],
            'status' => ['nullable', 'in:zero,positive'],
        ]);

        $filters = [
            'start_date' => $validated['start_date'] ?? CommodityPrice::min('price_date'),
            'end_date' => $validated['end_date'] ?? CommodityPrice::max('price_date'),
            'market_id' => $validated['market'] ?? null,
            'category_id' => $validated['category'] ?? null,
            'commodity_id' => $validated['commodity'] ?? null,
            'status' => $validated['status'] ?? null,
        ];

        return view('quality.index', array_merge($qualityService->report($filters), [
            'filters' => $filters,
            'markets' => Market::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'commodities' => Commodity::orderBy('name')->get(),
        ]));
    }
}
