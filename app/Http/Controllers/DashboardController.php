<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrediction;
use App\Models\CommodityPrice;
use App\Models\Market;
use App\Models\PredictionRun;
use App\Services\CommodityPriceService;
use App\Services\HolidayCalendarService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, CommodityPriceService $priceService, HolidayCalendarService $holidayCalendar): View
    {
        $latestDate = CommodityPrice::max('price_date') ?? now()->toDateString();
        $date = $request->string('date')->toString() ?: $latestDate;
        $marketId = $request->integer('market') ?: null;
        $categoryId = $request->integer('category') ?: null;
        $priceRows = $priceService->rows($date, $marketId, $categoryId);
        $commodityOptions = Commodity::where('is_active', true)->orderBy('name')->get();
        $selectedCommodity = Commodity::find($request->integer('chart_commodity'))
            ?? $commodityOptions->first();
        $chartDays = in_array($request->integer('chart_days'), [10, 15, 30], true)
            ? $request->integer('chart_days')
            : 30;
        $chartScale = in_array($request->string('chart_scale')->toString(), ['focus', 'zero'], true)
            ? $request->string('chart_scale')->toString()
            : 'focus';
        $chartHistory = $selectedCommodity
            ? $priceService->history($selectedCommodity, $chartDays, $marketId)
            : collect();

        $rawMinimum = (int) ($chartHistory->min('average_price') ?? 0);
        $rawMaximum = (int) ($chartHistory->max('average_price') ?? 0);
        if ($chartScale === 'zero') {
            $minimum = 0;
            $maximum = $rawMaximum;
        } else {
            $variation = max(1, $rawMaximum - $rawMinimum);
            $padding = max(1, (int) round($variation * .1));
            $minimum = max(0, $rawMinimum - $padding);
            $maximum = $rawMaximum + $padding;
        }
        $range = max(1, $maximum - $minimum);
        $pointCount = max(1, $chartHistory->count() - 1);
        $chartCoordinates = $chartHistory->values()->map(function ($point, $index) use ($range, $minimum, $pointCount, $holidayCalendar) {
            $x = round(($index / $pointCount) * 760, 2);
            $y = round(225 - (((int) $point->average_price - $minimum) / $range) * 195, 2);

            return [
                'x' => $x,
                'y' => $y,
                'price' => (int) $point->average_price,
                'date' => $point->price_date,
                'holiday' => $holidayCalendar->find($point->price_date),
            ];
        });
        $chartPoints = $chartCoordinates->map(fn ($point) => "{$point['x']},{$point['y']}")->implode(' ');
        $chartCalendar = $holidayCalendar->summarize($chartCoordinates);
        $nextChartHoliday = $holidayCalendar->nextAfter($chartCoordinates->last()['date'] ?? $latestDate);

        $latestChartPrice = (int) ($chartHistory->last()->average_price ?? 0);
        $previousChartPrice = (int) ($chartHistory->slice(-2, 1)->first()->average_price ?? $latestChartPrice);
        $chartChange = $latestChartPrice - $previousChartPrice;
        $chartPercentage = $previousChartPrice > 0 ? ($chartChange / $previousChartPrice) * 100 : 0;

        $upMovements = $priceRows->where('is_comparable', true)->where('trend', 'up')->sortByDesc('percentage_value')->take(5)->values();
        $downMovements = $priceRows->where('is_comparable', true)->where('trend', 'down')->sortBy('percentage_value')->take(5)->values();
        $activeRunId = PredictionRun::where('status', 'active')->value('id');
        $dashboardPrediction = CommodityPrediction::with('commodity')->where('normalized_status', 'LAYAK')
            ->when($activeRunId, fn ($query) => $query->where('prediction_run_id', $activeRunId))
            ->where('horizon_days', 7)->orderByDesc('accuracy_score')->first();

        return view('dashboard.index', [
            'commodities' => $priceRows->take(10),
            'categories' => Category::orderBy('name')->get(),
            'markets' => Market::where('is_active', true)->orderBy('name')->get(),
            'commodityOptions' => $commodityOptions,
            'selectedDate' => $date,
            'selectedMarket' => $marketId,
            'selectedCategory' => $categoryId,
            'selectedCommodity' => $selectedCommodity,
            'chartDays' => $chartDays,
            'chartScale' => $chartScale,
            'chartHistory' => $chartHistory,
            'chartPoints' => $chartPoints,
            'chartCoordinates' => $chartCoordinates,
            'chartCalendar' => $chartCalendar,
            'nextChartHoliday' => $nextChartHoliday,
            'chartMinimum' => $minimum,
            'chartMaximum' => $maximum,
            'latestChartPrice' => $latestChartPrice,
            'chartChange' => $chartChange,
            'chartPercentage' => $chartPercentage,
            'upMovements' => $upMovements,
            'downMovements' => $downMovements,
            'latestDate' => $latestDate,
            'dashboardPrediction' => $dashboardPrediction,
            'summary' => [
                'commodities' => $priceRows->count(),
                'up' => $priceRows->where('trend', 'up')->count(),
                'down' => $priceRows->where('trend', 'down')->count(),
                'stable' => $priceRows->where('trend', 'stable')->count(),
                'unavailable' => $priceRows->where('trend', 'unavailable')->count(),
                'markets' => Market::where('is_active', true)->count(),
                'categories' => Category::count(),
            ],
        ]);
    }
}
