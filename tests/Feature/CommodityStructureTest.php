<?php

namespace Tests\Feature;

use App\Models\Category;
use Database\Seeders\CommodityDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommodityStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_rice_category_has_medium_and_premium_variants(): void
    {
        $this->seed(CommodityDemoSeeder::class);

        $riceNames = Category::where('name', 'Beras')
            ->firstOrFail()
            ->commodities()
            ->pluck('name');

        $this->assertTrue($riceNames->contains('Beras Medium'));
        $this->assertTrue($riceNames->contains('Beras Premium'));
        $this->get('/harga-komoditas/beras-premium')->assertOk()->assertSee('Beras Premium');
        $this->get('/')->assertOk()->assertSee('Tren Harga Komoditas');
        $this->get('/?market=1&category=1')->assertOk();
    }
}
