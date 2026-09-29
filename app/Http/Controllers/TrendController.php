<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use App\Services\CommodityPriceService;
use App\Services\HolidayCalendarService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrendController extends Controller
{
    public function index(Request $request, CommodityPriceService $priceService, HolidayCalendarService $holidayCalendar): View
    {
        $commodityOptions = Commodity::where('is_active', true)->orderBy('name')->get();
        $selectedCommodity = Commodity::find($request->integer('chart_commodity')) ?? $commodityOptions->first();
        $marketId = $request->integer('market') ?: null;
        $latestDate = CommodityPrice::max('price_date') ?? now()->toDateString();
        $days = in_array($request->integer('chart_days'), [10, 15, 30, 90, 180, 365], true) ? $request->integer('chart_days') : 30;
        $scale = in_array($request->string('chart_scale')->toString(), ['focus', 'zero'], true) ? $request->string('chart_scale')->toString() : 'focus';
        $history = $selectedCommodity ? $priceService->history($selectedCommodity, $days, $marketId) : collect();
        $rawMinimum = (int) ($history->min('average_price') ?? 0);
        $rawMaximum = (int) ($history->max('average_price') ?? 0);
        if ($scale === 'zero') {
            $minimum = 0;
            $maximum = $rawMaximum;
        } else {
            $variation = max(1, $rawMaximum - $rawMinimum);
            $padding = max(1, (int) round($variation * .1));
            $minimum = max(0, $rawMinimum - $padding);
            $maximum = $rawMaximum + $padding;
        }
        $range = max(1, $maximum - $minimum);
        $pointCount = max(1, $history->count() - 1);
        $coordinates = $history->values()->map(fn ($point, $index) => [
            'x' => round(($index / $pointCount) * 760, 2),
            'y' => round(225 - (((int) $point->average_price - $minimum) / $range) * 195, 2),
            'price' => (int) $point->average_price, 'date' => $point->price_date,
            'holiday' => $holidayCalendar->find($point->price_date),
        ]);
        $chartCalendar = $holidayCalendar->summarize($coordinates);
        $nextChartHoliday = $holidayCalendar->nextAfter($coordinates->last()['date'] ?? $latestDate);
        $latest = (int) ($history->last()->average_price ?? 0);
        $previous = (int) ($history->slice(-2, 1)->first()->average_price ?? $latest);
        $change = $latest - $previous;
        $average = (int) round($history->avg('average_price') ?? 0);
        $volatility = $average > 0 ? (($rawMaximum - $rawMinimum) / $average) * 100 : 0;

        return view('trends.index', [
            'commodityOptions' => $commodityOptions, 'selectedCommodity' => $selectedCommodity, 'markets' => Market::where('is_active', true)->orderBy('name')->get(),
            'selectedMarket' => $marketId, 'selectedCategory' => null, 'selectedDate' => $latestDate, 'chartDays' => $days, 'chartScale' => $scale,
            'chartDayOptions' => [10, 15, 30, 90, 180, 365], 'chartHistory' => $history, 'chartCoordinates' => $coordinates,
            'chartCalendar' => $chartCalendar, 'nextChartHoliday' => $nextChartHoliday,
            'chartPoints' => $coordinates->map(fn ($point) => "{$point['x']},{$point['y']}")->implode(' '), 'chartMinimum' => $minimum, 'chartMaximum' => $maximum,
            'latestChartPrice' => $latest, 'chartChange' => $change, 'chartPercentage' => $previous > 0 ? ($change / $previous) * 100 : 0,
            'trendStats' => ['minimum' => $rawMinimum, 'maximum' => $rawMaximum, 'average' => $average, 'volatility' => $volatility, 'observations' => $history->count()],
        ]);
    }
}
