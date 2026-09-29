<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use App\Services\MarketComparisonChartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketComparisonChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_displays_four_toggleable_market_lines_in_fixed_order(): void
    {
        [$commodity, $markets] = $this->seedComparisonData();

        $response = $this->get(route('markets.index', [
            'commodity' => $commodity->id,
            'date' => '2026-08-30',
            'period' => 7,
        ]));

        $response->assertOk()
            ->assertSee('Empat garis dalam satu pandangan.')
            ->assertSee('METODE PEMERINGKATAN')
            ->assertSee('Harga Rp0 dianggap belum tercatat dan tidak dihitung.')
            ->assertSee('Rata-rata 4 pasar')
            ->assertSee('Rentang harga')
            ->assertSee('LINIMASA 7 HARI')
            ->assertSee('data-market-date-chip', false)
            ->assertSee('data-market-date-price-popup', false)
            ->assertSee('HARGA EMPAT PASAR')
            ->assertSee('data-market-comparison-chart', false)
            ->assertSee('data-market-show-all', false);

        $html = $response->getContent();
        $this->assertSame(4, substr_count($html, 'data-market-line-toggle='));
        $this->assertSame(4, substr_count($html, 'data-market-series='));

        foreach ($markets as $market) {
            $response->assertSee($market->name);
        }
    }

    public function test_zero_price_creates_a_gap_and_is_not_counted_as_available_data(): void
    {
        [$commodity] = $this->seedComparisonData();

        $chart = app(MarketComparisonChartService::class)->build($commodity, '2026-08-30', 7);
        $gempol = $chart['series']->firstWhere('name', 'Pasar Gempol Kerep');

        $this->assertTrue($chart['available']);
        $this->assertCount(4, $chart['series']);
        $this->assertSame(config('market-comparison.markets'), $chart['series']->pluck('name')->all());
        $this->assertSame(2, $gempol['available_days']);
        $this->assertCount(2, $gempol['segments']);
        $this->assertSame(14000, $chart['period_minimum']);
        $this->assertSame(15700, $chart['period_maximum']);
        $this->assertSame(1700, $chart['period_spread']);
        $this->assertSame('Pasar Gempol Kerep', $chart['lowest_observation']['market']);
        $this->assertSame('Poh Jejer', $chart['highest_observation']['market']);
        $this->assertCount(7, $chart['timeline']);
        $this->assertSame(4, $chart['timeline']->last()['available_markets']);
        $this->assertCount(4, $chart['timeline']->last()['prices']);
        $missingPoint = $gempol['points']->first(fn (array $point) => $point['date']->toDateString() === '2026-08-29');
        $this->assertSame(0, $missingPoint['price']);
    }

    private function seedComparisonData(): array
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $commodity = Commodity::create([
            'category_id' => $category->id,
            'name' => 'Beras Premium',
            'slug' => 'beras-premium',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $markets = collect(config('market-comparison.markets'))->map(fn (string $name) => Market::create([
            'name' => $name,
            'district' => 'Kabupaten Mojokerto',
            'regency' => 'Kabupaten Mojokerto',
            'is_active' => true,
        ]));

        foreach ($markets as $marketIndex => $market) {
            foreach (['2026-08-28', '2026-08-29', '2026-08-30'] as $dateIndex => $date) {
                CommodityPrice::create([
                    'commodity_id' => $commodity->id,
                    'market_id' => $market->id,
                    'price_date' => $date,
                    'price' => $marketIndex === 0 && $dateIndex === 1 ? 0 : 14000 + ($marketIndex * 500) + ($dateIndex * 100),
                    'source' => 'Pengujian',
                ]);
            }
        }

        return [$commodity, $markets];
    }
}
