<?php

namespace App\Services;

use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MarketComparisonChartService
{
    private const PLOT_LEFT = 72;

    private const PLOT_RIGHT = 1000;

    private const PLOT_TOP = 34;

    private const PLOT_BOTTOM = 280;

    public function __construct(private readonly HolidayCalendarService $holidayCalendar) {}

    public function build(Commodity $commodity, string $endDate, int $days): array
    {
        $end = CarbonImmutable::parse($endDate)->startOfDay();
        $start = $end->subDays($days - 1);
        $marketNames = collect(config('market-comparison.markets', []));
        $colors = collect(config('market-comparison.colors', []));
        $marketsByName = Market::query()
            ->where('is_active', true)
            ->whereIn('name', $marketNames)
            ->get()
            ->keyBy('name');
        $markets = $marketNames->map(fn (string $name) => $marketsByName->get($name))->filter()->values();
        $dates = collect(CarbonPeriod::create($start, $end))
            ->map(fn ($date) => CarbonImmutable::instance($date)->startOfDay())
            ->values();

        $priceRows = CommodityPrice::query()
            ->where('commodity_id', $commodity->id)
            ->whereIn('market_id', $markets->pluck('id'))
            ->whereDate('price_date', '>=', $start->toDateString())
            ->whereDate('price_date', '<=', $end->toDateString())
            ->get(['market_id', 'price_date', 'price'])
            ->groupBy('market_id')
            ->map(fn (Collection $rows) => $rows->keyBy(fn (CommodityPrice $row) => $row->price_date->format('Y-m-d')));

        $positivePrices = $priceRows->flatten()->pluck('price')->filter(fn ($price) => (int) $price > 0)->map(fn ($price) => (int) $price);

        if ($markets->isEmpty() || $positivePrices->isEmpty()) {
            return $this->emptyChart($start, $end, $days, $markets);
        }

        [$scaleMinimum, $scaleMaximum] = $this->scale($positivePrices);
        $series = $markets->map(function (Market $market, int $index) use ($colors, $dates, $priceRows, $scaleMinimum, $scaleMaximum): array {
            $marketPrices = $priceRows->get($market->id, collect());
            $points = $dates->map(function (CarbonImmutable $date, int $dateIndex) use ($dates, $marketPrices, $scaleMinimum, $scaleMaximum): array {
                $price = (int) ($marketPrices->get($date->toDateString())?->price ?? 0);

                return [
                    'date' => $date,
                    'price' => $price,
                    'x' => $this->x($dateIndex, $dates->count()),
                    'y' => $price > 0 ? $this->y($price, $scaleMinimum, $scaleMaximum) : null,
                ];
            })->values();
            $available = $points->where('price', '>', 0)->values();
            $latest = $available->last();
            $previous = $available->count() > 1 ? $available->get($available->count() - 2) : null;

            return [
                'key' => 'market-'.Str::slug($market->name).'-'.$market->id,
                'market_id' => $market->id,
                'name' => $market->name,
                'district' => $market->district ?: 'Kabupaten Mojokerto',
                'color' => $colors->get($index, '#64748b'),
                'points' => $points,
                'segments' => $this->segments($points),
                'latest' => (int) ($latest['price'] ?? 0),
                'latest_date' => $latest['date'] ?? null,
                'change' => $latest && $previous ? (int) $latest['price'] - (int) $previous['price'] : 0,
                'average' => $available->isNotEmpty() ? (int) round($available->avg('price')) : 0,
                'minimum' => (int) ($available->min('price') ?? 0),
                'maximum' => (int) ($available->max('price') ?? 0),
                'available_days' => $available->count(),
                'coverage' => $dates->isNotEmpty() ? round(($available->count() / $dates->count()) * 100, 1) : 0,
            ];
        })->values();

        $observations = $series->flatMap(fn (array $marketSeries) => $marketSeries['points']
            ->where('price', '>', 0)
            ->map(fn (array $point): array => [
                'market' => $marketSeries['name'],
                'color' => $marketSeries['color'],
                'date' => $point['date'],
                'price' => $point['price'],
            ]))
            ->values();
        $lowestObservation = $observations->sortBy('price')->first();
        $highestObservation = $observations->sortByDesc('price')->first();
        $periodAverage = (int) round($observations->avg('price'));
        $periodMinimum = (int) ($lowestObservation['price'] ?? 0);
        $periodMaximum = (int) ($highestObservation['price'] ?? 0);
        $timeline = $dates->map(function (CarbonImmutable $date, int $index) use ($series, $dates): array {
            $availableMarkets = $series->filter(fn (array $marketSeries) => ($marketSeries['points'][$index]['price'] ?? 0) > 0)->count();

            return [
                'date' => $date,
                'x' => $this->x($index, $dates->count()),
                'is_weekend' => $date->isWeekend(),
                'holiday' => $this->holidayCalendar->find($date),
                'available_markets' => $availableMarkets,
                'prices' => $series->map(fn (array $marketSeries): array => [
                    'market' => $marketSeries['name'],
                    'color' => $marketSeries['color'],
                    'price' => (int) ($marketSeries['points'][$index]['price'] ?? 0),
                ])->values(),
                'is_latest' => $index === $dates->count() - 1,
            ];
        })->values();

        return [
            'available' => true,
            'days' => $days,
            'start' => $start,
            'end' => $end,
            'series' => $series,
            'ticks' => $this->ticks($scaleMinimum, $scaleMaximum),
            'date_ticks' => $this->dateTicks($dates),
            'market_count' => $series->count(),
            'positive_points' => $series->sum('available_days'),
            'period_average' => $periodAverage,
            'period_minimum' => $periodMinimum,
            'period_maximum' => $periodMaximum,
            'period_spread' => max(0, $periodMaximum - $periodMinimum),
            'lowest_observation' => $lowestObservation,
            'highest_observation' => $highestObservation,
            'timeline' => $timeline,
            'scale_minimum' => $scaleMinimum,
            'scale_maximum' => $scaleMaximum,
        ];
    }

    private function scale(Collection $prices): array
    {
        $minimum = (int) $prices->min();
        $maximum = (int) $prices->max();
        $range = max(1, $maximum - $minimum);
        $padding = max(100, (int) round($range * .12));

        if ($minimum === $maximum) {
            $padding = max(500, (int) round($minimum * .05));
        }

        return [max(0, $minimum - $padding), $maximum + $padding];
    }

    private function x(int $index, int $count): float
    {
        if ($count <= 1) {
            return self::PLOT_LEFT;
        }

        return round(self::PLOT_LEFT + (($index / ($count - 1)) * (self::PLOT_RIGHT - self::PLOT_LEFT)), 2);
    }

    private function y(int $price, int $minimum, int $maximum): float
    {
        $ratio = ($price - $minimum) / max(1, $maximum - $minimum);

        return round(self::PLOT_BOTTOM - ($ratio * (self::PLOT_BOTTOM - self::PLOT_TOP)), 2);
    }

    private function segments(Collection $points): Collection
    {
        $segments = collect();
        $current = collect();

        foreach ($points as $point) {
            if ($point['y'] === null) {
                if ($current->isNotEmpty()) {
                    $segments->push($current->map(fn (array $item) => $item['x'].','.$item['y'])->implode(' '));
                    $current = collect();
                }

                continue;
            }

            $current->push($point);
        }

        if ($current->isNotEmpty()) {
            $segments->push($current->map(fn (array $item) => $item['x'].','.$item['y'])->implode(' '));
        }

        return $segments;
    }

    private function ticks(int $minimum, int $maximum): Collection
    {
        return collect(range(0, 3))->map(function (int $index) use ($minimum, $maximum): array {
            $ratio = $index / 3;

            return [
                'value' => (int) round($maximum - (($maximum - $minimum) * $ratio)),
                'y' => round(self::PLOT_TOP + ((self::PLOT_BOTTOM - self::PLOT_TOP) * $ratio), 2),
            ];
        });
    }

    private function dateTicks(Collection $dates): Collection
    {
        if ($dates->isEmpty()) {
            return collect();
        }

        $last = $dates->count() - 1;
        $indexes = collect([0, (int) round($last * .25), (int) round($last * .5), (int) round($last * .75), $last])->unique();

        return $indexes->map(fn (int $index): array => [
            'date' => $dates[$index],
            'x' => $this->x($index, $dates->count()),
        ])->values();
    }

    private function emptyChart(CarbonImmutable $start, CarbonImmutable $end, int $days, Collection $markets): array
    {
        return [
            'available' => false,
            'days' => $days,
            'start' => $start,
            'end' => $end,
            'series' => collect(),
            'ticks' => collect(),
            'date_ticks' => collect(),
            'market_count' => $markets->count(),
            'positive_points' => 0,
            'period_average' => 0,
            'period_minimum' => 0,
            'period_maximum' => 0,
            'period_spread' => 0,
            'lowest_observation' => null,
            'highest_observation' => null,
            'timeline' => collect(),
            'scale_minimum' => 0,
            'scale_maximum' => 0,
        ];
    }
}
