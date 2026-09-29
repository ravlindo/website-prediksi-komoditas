<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Services\CommodityPriceService;
use App\Services\MarketComparisonChartService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketComparisonController extends Controller
{
    public function index(
        Request $request,
        CommodityPriceService $service,
        MarketComparisonChartService $chartService,
    ): View {
        $commodities = Commodity::where('is_active', true)->orderBy('name')->get();
        $commodity = $commodities->firstWhere('id', $request->integer('commodity')) ?? $commodities->first();
        $date = $request->string('date')->toString() ?: CommodityPrice::max('price_date');
        $periodOptions = collect(config('market-comparison.periods', [7, 30, 90, 365]));
        $requestedPeriod = $request->integer('period');
        $period = $periodOptions->contains($requestedPeriod)
            ? $requestedPeriod
            : (int) config('market-comparison.default_period', 30);
        $rows = $commodity && $date ? $service->marketComparison($commodity, $date) : collect();
        $positive = $rows->where('price', '>', 0);
        $chart = $commodity && $date
            ? $chartService->build($commodity, $date, $period)
            : ['available' => false, 'series' => collect(), 'market_count' => 0, 'positive_points' => 0];

        return view('markets.index', compact(
            'commodities', 'commodity', 'date', 'periodOptions', 'period', 'rows', 'positive', 'chart'
        ));
    }
}
