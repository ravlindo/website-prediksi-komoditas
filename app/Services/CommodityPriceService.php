<?php

namespace App\Services;

use App\Models\Commodity;
use App\Models\CommodityPrice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CommodityPriceService
{
    public function rows(string $date, ?int $marketId = null, ?int $categoryId = null): Collection
    {
        $paired = $this->pairedAverages($date, CarbonImmutable::parse($date)->subDay()->toDateString(), $marketId);
        $current = $paired['current'];
        $previous = $paired['previous'];
        $comparable = $paired['comparable'];

        $categoryDisplay = config('commodity-display.categories', []);
        $commodityOrder = collect(config('commodity-display.commodities', []))->flip();
        $sectionRows = config('commodity-display.section_rows', []);

        return Commodity::query()
            ->with('category')
            ->where('is_active', true)
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('category_id')
            ->orderBy('name')
            ->get()
            ->filter(fn (Commodity $commodity) => $current->has($commodity->id))
            ->map(function (Commodity $commodity) use ($current, $previous, $comparable, $categoryDisplay, $commodityOrder, $sectionRows) {
                $currentPrice = (int) round($current[$commodity->id]);
                $previousPrice = (int) round($previous[$commodity->id] ?? $currentPrice);
                $change = $currentPrice - $previousPrice;
                $isComparable = ($comparable[$commodity->id] ?? false) && $currentPrice > 0 && $previousPrice > 0;
                $percentage = $isComparable ? ($change / $previousPrice) * 100 : 0;
                $trend = ! $isComparable ? 'unavailable' : ($change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'));
                $categoryKey = mb_strtolower($commodity->category->name);
                $commodityKey = mb_strtolower($commodity->name);
                $display = $categoryDisplay[$categoryKey] ?? ['number' => 999, 'order' => 999];

                return [
                    'id' => $commodity->id,
                    'slug' => $commodity->slug,
                    'icon' => collect(explode(' ', $commodity->name))->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->join(''),
                    'tone' => $this->tone($commodity->category->slug),
                    'name' => $commodity->name,
                    'category' => $commodity->category->name,
                    'category_number' => $display['number'],
                    'category_order' => $display['order'],
                    'commodity_order' => $commodityOrder->get($commodityKey, 999),
                    'is_section' => in_array($commodityKey, $sectionRows, true),
                    'unit' => $commodity->unit,
                    'previous' => $this->rupiah($previousPrice),
                    'current' => $this->rupiah($currentPrice),
                    'previous_value' => $previousPrice,
                    'current_value' => $currentPrice,
                    'change_value' => $change,
                    'percentage_value' => $percentage,
                    'is_comparable' => $isComparable,
                    'change' => ($change > 0 ? '+' : ($change < 0 ? '-' : '')).$this->rupiah(abs($change)),
                    'percent' => ($percentage > 0 ? '+' : '').number_format($percentage, 2, ',', '.').'%',
                    'trend' => $trend,
                    'label' => ['up' => 'Naik', 'down' => 'Turun', 'stable' => 'Stabil', 'unavailable' => 'Data kosong'][$trend],
                ];
            })
            ->sortBy(fn (array $row) => sprintf('%03d-%03d-%s', $row['category_order'], $row['commodity_order'], $row['name']))
            ->values();
    }

    public function history(Commodity $commodity, int $days = 30, ?int $marketId = null): Collection
    {
        return CommodityPrice::query()
            ->where('commodity_id', $commodity->id)
            ->when($marketId, fn ($query) => $query->where('market_id', $marketId))
            ->selectRaw('price_date, ROUND(AVG(NULLIF(price, 0))) as average_price, MIN(NULLIF(price, 0)) as minimum_price, MAX(NULLIF(price, 0)) as maximum_price')
            ->groupBy('price_date')
            ->havingRaw('COUNT(NULLIF(price, 0)) > 0')
            ->orderByDesc('price_date')
            ->limit($days)
            ->get()
            ->sortBy('price_date')
            ->values();
    }

    public function marketComparison(Commodity $commodity, string $date): Collection
    {
        return CommodityPrice::query()
            ->with('market')
            ->where('commodity_id', $commodity->id)
            ->whereDate('price_date', $date)
            ->whereHas('market', fn ($query) => $query
                ->where('is_active', true)
                ->whereIn('name', config('market-comparison.markets', [])))
            ->orderByRaw('CASE WHEN price = 0 THEN 1 ELSE 0 END')
            ->orderBy('price')
            ->get();
    }

    private function pairedAverages(string $currentDate, string $previousDate, ?int $marketId): array
    {
        $baseQuery = CommodityPrice::query()
            ->when($marketId, fn ($query) => $query->where('market_id', $marketId))
            ->select(['commodity_id', 'market_id', 'price_date', 'price']);
        $currentRows = (clone $baseQuery)->whereDate('price_date', $currentDate)->get()->groupBy('commodity_id');
        $previousRows = (clone $baseQuery)->whereDate('price_date', $previousDate)->get()->groupBy('commodity_id');
        $current = collect();
        $previous = collect();
        $comparable = collect();

        foreach ($currentRows as $commodityId => $todayRows) {
            $today = $todayRows->keyBy('market_id');
            $prior = ($previousRows[$commodityId] ?? collect())->keyBy('market_id');
            $commonMarkets = $today->keys()->intersect($prior->keys())->filter(
                fn ($id) => (int) $today[$id]->price > 0 && (int) $prior[$id]->price > 0
            );

            if ($commonMarkets->isNotEmpty()) {
                $current[$commodityId] = $commonMarkets->avg(fn ($id) => (int) $today[$id]->price);
                $previous[$commodityId] = $commonMarkets->avg(fn ($id) => (int) $prior[$id]->price);
                $comparable[$commodityId] = true;
            } else {
                $positiveToday = $today->where('price', '>', 0);
                $current[$commodityId] = $positiveToday->isNotEmpty() ? $positiveToday->avg('price') : 0;
                $previous[$commodityId] = $current[$commodityId];
                $comparable[$commodityId] = false;
            }
        }

        return compact('current', 'previous', 'comparable');
    }

    private function rupiah(int $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    private function tone(string $category): string
    {
        return match ($category) {
            'beras' => 'green-bg', 'cabai' => 'red-bg', 'bawang' => 'orange-bg',
            'telur' => 'yellow-bg', 'minyak-goreng' => 'blue-bg', default => 'gray-bg',
        };
    }
}
