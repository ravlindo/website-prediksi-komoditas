<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendHolidayTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_holiday_is_marked_on_trend_chart_and_timeline(): void
    {
        [$commodity] = $this->createHolidayHistory();

        $response = $this->get(route('trends.index', ['chart_commodity' => $commodity->id]));

        $response->assertOk()
            ->assertSee('Hari Proklamasi Kemerdekaan')
            ->assertSee('tanggal merah tercatat')
            ->assertSee('holiday national-holiday', false)
            ->assertSee('data-holiday-jump="2"', false);
    }

    public function test_dashboard_uses_the_same_holiday_calendar_component(): void
    {
        [$commodity] = $this->createHolidayHistory();

        $this->get(route('dashboard', ['chart_commodity' => $commodity->id]))
            ->assertOk()
            ->assertSee('Hari Proklamasi Kemerdekaan')
            ->assertSee('Libur nasional');
    }

    private function createHolidayHistory(): array
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $commodity = Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Uji', 'is_active' => true]);

        foreach (['2026-08-15', '2026-08-16', '2026-08-17', '2026-08-18'] as $index => $date) {
            CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => $date, 'price' => 15000 + ($index * 100), 'source' => 'Pengujian kalender']);
        }

        return [$commodity, $market];
    }
}
