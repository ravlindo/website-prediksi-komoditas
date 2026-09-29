<?php

namespace App\Services;

use App\Models\CommodityPrice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DataQualityService
{
    public function report(array $filters): array
    {
        $base = $this->filteredQuery($filters);
        $summary = (clone $base)->selectRaw(
            'COUNT(*) as total_count, '
            .'SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as zero_count, '
            .'SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as positive_count, '
            .'COUNT(DISTINCT price_date) as date_count, '
            .'COUNT(DISTINCT commodity_id) as commodity_count, '
            .'COUNT(DISTINCT market_id) as market_count'
        )->first();

        $total = (int) ($summary->total_count ?? 0);
        $positive = (int) ($summary->positive_count ?? 0);

        $duplicates = DB::query()->fromSub(
            (clone $base)
                ->selectRaw('commodity_id, market_id, price_date, COUNT(*) as duplicate_count')
                ->groupBy('commodity_id', 'market_id', 'price_date'),
            'duplicate_groups'
        )->where('duplicate_count', '>', 1)->count();

        return [
            'summary' => [
                'total' => $total,
                'zero' => (int) ($summary->zero_count ?? 0),
                'positive' => $positive,
                'completeness' => $total > 0 ? round(($positive / $total) * 100, 2) : 0,
                'dates' => (int) ($summary->date_count ?? 0),
                'commodities' => (int) ($summary->commodity_count ?? 0),
                'markets' => (int) ($summary->market_count ?? 0),
                'duplicates' => $duplicates,
            ],
            'commodityIssues' => (clone $base)
                ->join('commodities', 'commodity_prices.commodity_id', '=', 'commodities.id')
                ->join('categories', 'commodities.category_id', '=', 'categories.id')
                ->selectRaw('commodities.id, commodities.name, commodities.unit, categories.name as category_name, COUNT(*) as total_count, SUM(CASE WHEN commodity_prices.price = 0 THEN 1 ELSE 0 END) as zero_count')
                ->groupBy('commodities.id', 'commodities.name', 'commodities.unit', 'categories.name')
                ->orderByDesc('zero_count')
                ->limit(10)
                ->get(),
            'marketQuality' => (clone $base)
                ->join('markets', 'commodity_prices.market_id', '=', 'markets.id')
                ->selectRaw('markets.id, markets.name, COUNT(*) as total_count, SUM(CASE WHEN commodity_prices.price = 0 THEN 1 ELSE 0 END) as zero_count, SUM(CASE WHEN commodity_prices.price > 0 THEN 1 ELSE 0 END) as positive_count')
                ->groupBy('markets.id', 'markets.name')
                ->orderBy('markets.name')
                ->get(),
            'lowestDates' => (clone $base)
                ->selectRaw('price_date, COUNT(*) as total_count, SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as zero_count, SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as positive_count')
                ->groupBy('price_date')
                ->orderByRaw('(SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) * 1.0 / COUNT(*)) ASC')
                ->orderByDesc('price_date')
                ->limit(10)
                ->get(),
            'records' => (clone $base)
                ->with(['commodity.category', 'market'])
                ->orderByDesc('price_date')
                ->orderBy('price')
                ->paginate(25)
                ->withQueryString(),
        ];
    }

    private function filteredQuery(array $filters): Builder
    {
        return CommodityPrice::query()
            ->when($filters['start_date'] ?? null, fn (Builder $query, $date) => $query->whereDate('price_date', '>=', $date))
            ->when($filters['end_date'] ?? null, fn (Builder $query, $date) => $query->whereDate('price_date', '<=', $date))
            ->when($filters['market_id'] ?? null, fn (Builder $query, $id) => $query->where('market_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('commodity', fn (Builder $commodity) => $commodity->where('category_id', $id)))
            ->when($filters['commodity_id'] ?? null, fn (Builder $query, $id) => $query->where('commodity_id', $id))
            ->when(($filters['status'] ?? null) === 'zero', fn (Builder $query) => $query->where('price', 0))
            ->when(($filters['status'] ?? null) === 'positive', fn (Builder $query) => $query->where('price', '>', 0));
    }
}
