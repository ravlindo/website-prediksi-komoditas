<?php

namespace App\Services;

use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\PredictionRun;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class PredictionChartService
{
    private const WIDTH = 1000;

    private const HEIGHT = 340;

    private const LEFT = 72;

    private const RIGHT = 970;

    private const TOP = 42;

    private const BOTTOM = 286;

    public function build(Commodity $commodity, PredictionRun $run, int $selectedHorizon, bool $showAuditValues): ?array
    {
        $predictions = $commodity->predictions->sortBy('horizon_days')->values();
        if ($predictions->isEmpty() || ! $run->data_last_date) {
            return null;
        }

        $cutoff = CarbonImmutable::parse($run->data_last_date)->startOfDay();
        $latestActualDate = CommodityPrice::where('commodity_id', $commodity->id)->where('price', '>', 0)->max('price_date');
        $latestActual = $latestActualDate ? CarbonImmutable::parse($latestActualDate)->startOfDay() : $cutoff;
        $lastTarget = $predictions->max(fn ($item) => CarbonImmutable::parse($item->target_date)->getTimestamp());
        $end = collect([$latestActual, CarbonImmutable::createFromTimestamp($lastTarget)])->sortBy(fn ($date) => $date->getTimestamp())->last();
        $start = $cutoff->subDays(29);

        $actual = CommodityPrice::query()
            ->where('commodity_id', $commodity->id)
            ->where('price', '>', 0)
            ->whereBetween('price_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('price_date, AVG(price) as average_price')
            ->groupBy('price_date')
            ->orderBy('price_date')
            ->get()
            ->map(fn ($row) => [
                'date' => CarbonImmutable::parse($row->price_date),
                'value' => (float) $row->average_price,
            ]);

        $visibleForecasts = $predictions->filter(
            fn ($prediction) => $prediction->normalized_status !== 'BELUM LAYAK' || $showAuditValues
        );
        $values = $actual->pluck('value')
            ->merge($visibleForecasts->flatMap(fn ($prediction) => [
                (float) $prediction->predicted_price,
                (float) $prediction->lower_bound,
                (float) $prediction->upper_bound,
            ]));
        if ($values->isEmpty()) {
            return null;
        }

        [$minimum, $maximum] = $this->scale($values);
        $actualPoints = $actual->map(fn ($point) => $this->actualPoint($point, $start, $end, $minimum, $maximum))->values();
        $cutoffActual = $actual->filter(fn ($point) => $point['date']->lessThanOrEqualTo($cutoff))->last();
        $forecastPoints = $predictions->map(function ($prediction) use ($start, $end, $minimum, $maximum, $selectedHorizon, $showAuditValues) {
            $published = $prediction->normalized_status !== 'BELUM LAYAK';
            $visible = $published || $showAuditValues;
            $date = CarbonImmutable::parse($prediction->target_date);
            $value = (float) $prediction->predicted_price;

            return [
                'horizon' => (int) $prediction->horizon_days,
                'date' => $date,
                'date_iso' => $date->toDateString(),
                'date_label' => $date->translatedFormat('d M Y'),
                'value' => $visible ? $value : null,
                'value_label' => $visible ? 'Rp '.number_format($value, 0, ',', '.') : 'Ditahan',
                'lower_label' => $visible ? 'Rp '.number_format((float) $prediction->lower_bound, 0, ',', '.') : null,
                'upper_label' => $visible ? 'Rp '.number_format((float) $prediction->upper_bound, 0, ',', '.') : null,
                'status' => $prediction->normalized_status,
                'status_label' => $published ? ($prediction->normalized_status === 'STABIL' ? 'Proyeksi stabil' : 'Layak dipublikasikan') : 'Ditahan untuk evaluasi',
                'published' => $published,
                'visible' => $visible,
                'selected' => (int) $prediction->horizon_days === $selectedHorizon,
                'x' => $this->x($date, $start, $end),
                'y' => $visible ? $this->y($value, $minimum, $maximum) : null,
                'lower_y' => $visible ? $this->y((float) $prediction->lower_bound, $minimum, $maximum) : null,
                'upper_y' => $visible ? $this->y((float) $prediction->upper_bound, $minimum, $maximum) : null,
            ];
        })->values();

        $publishedForecasts = $forecastPoints->filter(fn ($point) => $point['published'] && $point['visible']);
        $forecastLine = collect();
        if ($cutoffActual) {
            $forecastLine->push([
                'x' => $this->x($cutoff, $start, $end),
                'y' => $this->y($cutoffActual['value'], $minimum, $maximum),
            ]);
        }
        $forecastLine = $forecastLine->merge($publishedForecasts->map(fn ($point) => ['x' => $point['x'], 'y' => $point['y']]));

        return [
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'left' => self::LEFT,
            'right' => self::RIGHT,
            'top' => self::TOP,
            'bottom' => self::BOTTOM,
            'actual' => $actualPoints,
            'actual_polyline' => $this->polyline($actualPoints),
            'actual_area' => $this->area($actualPoints),
            'forecasts' => $forecastPoints,
            'forecast_polyline' => $this->polyline($forecastLine),
            'y_ticks' => $this->yTicks($minimum, $maximum),
            'x_ticks' => $this->xTicks($start, $end),
            'cutoff' => [
                'date' => $cutoff,
                'label' => $cutoff->translatedFormat('d M Y'),
                'x' => $this->x($cutoff, $start, $end),
            ],
            'latest_actual' => [
                'date' => $latestActual,
                'label' => $latestActual->translatedFormat('d M Y'),
            ],
            'is_stale' => $latestActual->greaterThan($cutoff),
            'selected' => $forecastPoints->firstWhere('selected', true),
            'show_audit_values' => $showAuditValues,
        ];
    }

    private function actualPoint(array $point, CarbonImmutable $start, CarbonImmutable $end, float $minimum, float $maximum): array
    {
        return [
            'date_iso' => $point['date']->toDateString(),
            'date_label' => $point['date']->translatedFormat('d M Y'),
            'value' => $point['value'],
            'value_label' => 'Rp '.number_format($point['value'], 0, ',', '.'),
            'x' => $this->x($point['date'], $start, $end),
            'y' => $this->y($point['value'], $minimum, $maximum),
        ];
    }

    private function scale(Collection $values): array
    {
        $minimum = (float) $values->min();
        $maximum = (float) $values->max();
        $range = max($maximum - $minimum, max($maximum * .04, 1));
        $padding = $range * .16;

        return [max(0, $minimum - $padding), $maximum + $padding];
    }

    private function x(CarbonImmutable $date, CarbonImmutable $start, CarbonImmutable $end): float
    {
        $days = max(1, $start->diffInDays($end));
        $offset = $start->diffInDays($date, false);

        return round(self::LEFT + (($offset / $days) * (self::RIGHT - self::LEFT)), 2);
    }

    private function y(float $value, float $minimum, float $maximum): float
    {
        return round(self::BOTTOM - ((($value - $minimum) / max(1, $maximum - $minimum)) * (self::BOTTOM - self::TOP)), 2);
    }

    private function polyline(Collection $points): string
    {
        return $points->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ');
    }

    private function area(Collection $points): string
    {
        if ($points->isEmpty()) {
            return '';
        }

        return $points->first()['x'].','.self::BOTTOM.' '.$this->polyline($points).' '.$points->last()['x'].','.self::BOTTOM;
    }

    private function yTicks(float $minimum, float $maximum): array
    {
        return collect(range(0, 3))->map(function ($index) use ($minimum, $maximum) {
            $ratio = $index / 3;
            $value = $maximum - (($maximum - $minimum) * $ratio);

            return [
                'value' => $value,
                'label' => 'Rp '.number_format($value, 0, ',', '.'),
                'y' => round(self::TOP + ($ratio * (self::BOTTOM - self::TOP)), 2),
            ];
        })->all();
    }

    private function xTicks(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $days = max(1, $start->diffInDays($end));

        return collect(range(0, 4))->map(function ($index) use ($start, $end, $days) {
            $date = $start->addDays((int) round(($days / 4) * $index));

            return [
                'label' => $date->translatedFormat('d M'),
                'x' => $this->x($date, $start, $end),
            ];
        })->all();
    }
}
