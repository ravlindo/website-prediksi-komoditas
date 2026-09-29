<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use App\Services\CommodityPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommodityDisplayOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_rows_follow_siskaperbapo_order_and_original_numbers(): void
    {
        $market = Market::create(['name' => 'Pasar Uji', 'slug' => 'pasar-uji', 'is_active' => true]);
        foreach ([['Bawang', 'Bawang Merah'], ['Beras', 'Beras Medium'], ['Beras', 'Beras Premium'], ['Gula', 'Gula Kristal Putih']] as [$categoryName, $commodityName]) {
            $category = Category::firstOrCreate(['name' => $categoryName], ['slug' => str($categoryName)->slug()]);
            $commodity = Commodity::create(['category_id' => $category->id, 'name' => $commodityName, 'slug' => str($commodityName)->slug(), 'unit' => 'kg', 'is_active' => true]);
            CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-12', 'price' => 10000, 'source' => 'Uji']);
        }

        $rows = app(CommodityPriceService::class)->rows('2026-08-12');

        $this->assertSame(['Beras Premium', 'Beras Medium', 'Gula Kristal Putih', 'Bawang Merah'], $rows->pluck('name')->all());
        $this->assertSame([1, 1, 2, 12], $rows->pluck('category_number')->all());
    }
}
