<?php

namespace App\Services;

use Illuminate\Support\Collection;
use RuntimeException;

class MarketPredictionEvaluationService
{
    private static ?Collection $memoizedRows = null;
    private static ?Collection $memoizedAllRows = null;

    public function build(?string $commodity = null, ?string $market = null, int $period = 30): array
    {
        $rows = $this->readFinalRows();
        $pairs = $rows->groupBy(fn (array $row) => $row['commodity'].'|'.$row['market']);
        $commodities = $rows->pluck('commodity')->unique()->sort()->values();
        $markets = $rows->pluck('market')->unique()->sort()->values();

        $selectedCommodity = $commodities->contains($commodity) ? $commodity : $commodities->first();
        $availableMarkets = $rows->where('commodity', $selectedCommodity)->pluck('market')->unique()->sort()->values();
        $selectedMarket = $availableMarkets->contains($market) ? $market : $availableMarkets->first();
        $selectedRows = $rows
            ->where('commodity', $selectedCommodity)
            ->where('market', $selectedMarket)
            ->sortBy('date')
            ->values();

        $period = in_array($period, [7, 30, 60, 74], true) ? $period : 30;
        $selectedRows = $selectedRows->take(-$period)->values();
        $pairMetrics = $pairs->map(function (Collection $items): array {
            $first = $items->first();

            return [
                'commodity' => $first['commodity'],
                'market' => $first['market'],
                'model' => $first['model'],
                'model_detail' => $first['model_detail'],
                'mae' => $first['mae'],
                'rmse' => $first['rmse'],
                'mape' => $first['mape'],
            ];
        })->values();

        return [
            'commodities' => $commodities,
            'markets' => $availableMarkets,
            'selectedCommodity' => $selectedCommodity,
            'selectedMarket' => $selectedMarket,
            'selectedPeriod' => $period,
            'summary' => [
                'commodities' => $commodities->count(),
                'markets' => $markets->count(),
                'pairs' => $pairs->count(),
                'points' => $rows->count(),
                'average_mape' => round((float) $pairMetrics->avg('mape'), 2),
                'generated_at' => '11 September 2026 07:33',
                'date_start' => $rows->min('date'),
                'date_end' => $rows->max('date'),
            ],
            'models' => $pairMetrics->groupBy('model')->map(fn (Collection $items, string $name) => [
                'name' => $name,
                'pairs' => $items->count(),
                'average_mape' => round((float) $items->avg('mape'), 2),
            ])->sortByDesc('pairs')->values(),
            'comparison' => $this->makeMarketComparison($rows, $selectedCommodity, $period),
            'modelComparison' => $this->makeModelComparison(),
            'result' => $this->makeResult($selectedRows),
            'chart' => $this->makeChart($selectedRows),
        ];
    }

    private function readFinalRows(): Collection
    {
        if (self::$memoizedRows !== null) {
            return self::$memoizedRows;
        }

        $path = config('prediction-evaluation.market_comparison_file');
        if (! is_string($path) || ! is_file($path)) {
            throw new RuntimeException('File evaluasi prediksi per pasar belum tersedia.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('File evaluasi prediksi per pasar tidak dapat dibaca.');
        }

        $headers = array_map(
            fn (string $header) => preg_replace('/^\xEF\xBB\xBF/', '', trim($header)),
            fgetcsv($handle) ?: []
        );
        $rows = collect();
        while (($values = fgetcsv($handle)) !== false) {
            if (count($values) !== count($headers)) {
                continue;
            }
            $row = array_combine($headers, $values);

            // File sumber memuat semua kandidat. Hanya keluaran model pemenang
            // yang boleh ditampilkan agar satu tanggal tidak muncul berulang.
            if (trim($row['Model_Group']) !== trim($row['Model_Final'])) {
                continue;
            }

            $rows->push([
                'date' => trim($row['Tanggal']),
                'commodity' => trim($row['Komoditas']),
                'market' => trim($row['Pasar']),
                'actual' => (float) $row['Aktual'],
                'forecast' => (float) $row['Forecast'],
                'model' => trim($row['Model_Final']),
                'model_detail' => trim($row['Detail_Model']),
                'mae' => (float) $row['MAE'],
                'rmse' => (float) $row['RMSE'],
                'mape' => (float) $row['MAPE (%)'],
                'difference' => (float) $row['Selisih'],
            ]);
        }
        fclose($handle);

        return self::$memoizedRows = $rows;
    }

    private function readAllRows(): Collection
    {
        if (self::$memoizedAllRows !== null) {
            return self::$memoizedAllRows;
        }

        $path = config('prediction-evaluation.market_comparison_file');
        if (! is_string($path) || ! is_file($path)) {
            throw new RuntimeException('File evaluasi prediksi per pasar belum tersedia.');
        }

        $handle = fopen($path, 'rb');
        $headers = array_map(
            fn (string $header) => preg_replace('/^\xEF\xBB\xBF/', '', trim($header)),
            fgetcsv($handle) ?: []
        );
        $rows = collect();
        while (($values = fgetcsv($handle)) !== false) {
            if (count($values) !== count($headers)) {
                continue;
            }
            $row = array_combine($headers, $values);
            $rows->push([
                'date' => trim($row['Tanggal']),
                'commodity' => trim($row['Komoditas']),
                'market' => trim($row['Pasar']),
                'actual' => (float) $row['Aktual'],
                'forecast' => (float) $row['Forecast'],
                'model_group' => trim($row['Model_Group']),
                'final_model' => trim($row['Model_Final']),
                'model_detail' => trim($row['Detail_Model']),
            ]);
        }
        fclose($handle);

        return self::$memoizedAllRows = $rows;
    }

    private function makeMarketComparison(Collection $finalRows, string $commodity, int $period): array
    {
        $palette = [
            'Mojosari' => ['actual' => '#2d72dc', 'forecast' => '#65a3ff'],
            'Kedungmaling' => ['actual' => '#ef5e6a', 'forecast' => '#f6a5ad'],
        ];
        $marketRows = collect($palette)->mapWithKeys(function (array $colors, string $market) use ($finalRows, $commodity, $period) {
            $items = $finalRows->where('commodity', $commodity)->where('market', $market)->sortBy('date')->take(-$period)->values();

            return [$market => ['colors' => $colors, 'rows' => $items]];
        });
        $values = $marketRows->flatMap(fn (array $series) => $series['rows']->flatMap(fn (array $row) => [$row['actual'], $row['forecast']]));
        $min = (float) $values->min();
        $max = (float) $values->max();
        $padding = max(($max - $min) * .12, max($max * .02, 1));
        $min -= $padding;
        $max += $padding;
        $span = max($max - $min, 1);
        $maxCount = max((int) $marketRows->max(fn (array $series) => $series['rows']->count()), 1);

        $series = $marketRows->map(function (array $marketSeries, string $market) use ($min, $span, $maxCount) {
            $points = $marketSeries['rows']->values()->map(function (array $row, int $index) use ($min, $span, $maxCount) {
                $x = 66 + ($index / max($maxCount - 1, 1)) * 868;

                return $row + [
                    'x' => round($x, 2),
                    'actual_y' => round(300 - (($row['actual'] - $min) / $span) * 225, 2),
                    'forecast_y' => round(300 - (($row['forecast'] - $min) / $span) * 225, 2),
                ];
            });
            $latest = $marketSeries['rows']->last();
            $previous = $marketSeries['rows']->slice(-2, 1)->first() ?? $latest;
            $change = $latest ? $latest['actual'] - $previous['actual'] : 0;

            return [
                'market' => $market,
                'colors' => $marketSeries['colors'],
                'points' => $points,
                'actual_polyline' => $points->map(fn ($point) => $point['x'].','.$point['actual_y'])->implode(' '),
                'forecast_polyline' => $points->map(fn ($point) => $point['x'].','.$point['forecast_y'])->implode(' '),
                'latest' => $latest,
                'trend' => $change > 0 ? 'naik' : ($change < 0 ? 'turun' : 'stabil'),
                'change' => $change,
                'model' => $latest['model'] ?? '-',
                'accuracy_score' => isset($latest['mape']) ? max(0, 100 - $latest['mape']) : 0,
            ];
        });

        $latestPrices = $series->pluck('latest.actual')->filter();
        $gap = $latestPrices->count() === 2 ? abs((float) $latestPrices->max() - (float) $latestPrices->min()) : 0;
        $gapPercent = $latestPrices->count() === 2 && (float) $latestPrices->min() > 0 ? $gap / (float) $latestPrices->min() * 100 : 0;
        $dates = $marketRows->flatMap(fn (array $item) => $item['rows']->pluck('date'))->unique()->sort()->values();
        $table = $dates->map(function (string $date) use ($marketRows) {
            $mojosari = $marketRows['Mojosari']['rows']->firstWhere('date', $date);
            $kedungmaling = $marketRows['Kedungmaling']['rows']->firstWhere('date', $date);
            $gap = ($mojosari && $kedungmaling) ? $mojosari['actual'] - $kedungmaling['actual'] : null;

            return compact('date', 'mojosari', 'kedungmaling', 'gap');
        })->sortByDesc('date')->values();

        return [
            'series' => $series,
            'table' => $table,
            'gap' => $gap,
            'gap_percent' => $gapPercent,
            'min' => $min,
            'mid' => ($min + $max) / 2,
            'max' => $max,
            'start' => $dates->first(),
            'end' => $dates->last(),
        ];
    }

    private function makeModelComparison(): Collection
    {
        $models = ['SARIMA', 'Prophet', 'XGBoost'];

        return $this->readAllRows()
            ->groupBy(fn (array $row) => $row['commodity'].'|'.$row['market'])
            ->map(function (Collection $pairRows) use ($models) {
                $first = $pairRows->first();
                $actuals = $pairRows->where('model_group', $first['final_model'])->pluck('actual');
                $unique = $actuals->unique()->count();
                $mean = max((float) $actuals->avg(), 1);
                $maxJump = $actuals->values()->map(fn ($value, $index) => $index ? abs($value - $actuals->values()[$index - 1]) : 0)->max();
                $reason = $unique <= 2 ? 'Data beku atau hampir konstan' : ($maxJump / $mean >= .15 ? 'Terdapat perubahan level harga' : ($first['final_model'] === 'SARIMA' ? 'Pola deret waktu sesuai SARIMA' : 'Galat validasi paling rendah'));
                $metrics = collect($models)->mapWithKeys(function (string $model) use ($pairRows) {
                    $candidate = $pairRows->where('model_group', $model)->values();
                    if ($candidate->isEmpty()) {
                        return [$model => null];
                    }
                    $errors = $candidate->map(fn (array $row) => $row['forecast'] - $row['actual']);
                    $absolute = $errors->map(fn ($error) => abs($error));
                    $percentage = $candidate->map(fn (array $row) => $row['actual'] != 0 ? abs(($row['forecast'] - $row['actual']) / $row['actual']) * 100 : null)->filter(fn ($value) => $value !== null);

                    return [$model => [
                        'mae' => (float) $absolute->avg(),
                        'rmse' => sqrt((float) $errors->map(fn ($error) => $error ** 2)->avg()),
                        'mape' => (float) $percentage->avg(),
                    ]];
                });

                return [
                    'commodity' => $first['commodity'],
                    'market' => $first['market'],
                    'selected_model' => $first['final_model'],
                    'reason' => $reason,
                    'metrics' => $metrics,
                ];
            })->sortBy(fn (array $row) => $row['commodity'].'|'.$row['market'])->values();
    }

    private function makeResult(Collection $rows): array
    {
        $first = $rows->first();
        $latest = $rows->last();
        if (! $first || ! $latest) {
            return [];
        }
        $mape = (float) $first['mape'];

        return [
            'commodity' => $first['commodity'],
            'market' => $first['market'],
            'model' => $first['model'],
            'model_detail' => $first['model_detail'],
            'mae' => $first['mae'],
            'rmse' => $first['rmse'],
            'mape' => $mape,
            'accuracy_score' => max(0, 100 - $mape),
            'latest' => $latest,
            'quality' => $mape <= 5 ? 'Sangat baik' : ($mape <= 10 ? 'Baik' : ($mape <= 15 ? 'Layak' : 'Perlu evaluasi')),
        ];
    }

    private function makeChart(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return ['points' => [], 'actual_polyline' => '', 'forecast_polyline' => ''];
        }

        $values = $rows->flatMap(fn (array $row) => [$row['actual'], $row['forecast']]);
        $min = (float) $values->min();
        $max = (float) $values->max();
        $padding = max(($max - $min) * .12, max($max * .02, 1));
        $min -= $padding;
        $max += $padding;
        $span = max($max - $min, 1);
        $count = max($rows->count() - 1, 1);

        $points = $rows->values()->map(function (array $row, int $index) use ($min, $span, $count): array {
            $x = 60 + ($index / $count) * 880;
            $actualY = 300 - (($row['actual'] - $min) / $span) * 230;
            $forecastY = 300 - (($row['forecast'] - $min) / $span) * 230;

            return $row + ['x' => round($x, 2), 'actual_y' => round($actualY, 2), 'forecast_y' => round($forecastY, 2)];
        });

        return [
            'points' => $points,
            'actual_polyline' => $points->map(fn ($point) => $point['x'].','.$point['actual_y'])->implode(' '),
            'forecast_polyline' => $points->map(fn ($point) => $point['x'].','.$point['forecast_y'])->implode(' '),
            'min' => $min,
            'mid' => ($min + $max) / 2,
            'max' => $max,
            'start' => $rows->first()['date'],
            'end' => $rows->last()['date'],
        ];
    }
}
