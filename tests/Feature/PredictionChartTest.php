<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrediction;
use App\Models\CommodityPrice;
use App\Models\Market;
use App\Models\PredictionRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_prediction_page_charts_actual_and_forecast_data_without_leaking_withheld_value_to_guest(): void
    {
        $category = Category::create(['name' => 'Bawang', 'slug' => 'bawang']);
        $commodity = Commodity::create([
            'category_id' => $category->id,
            'name' => 'Bawang Merah',
            'slug' => 'bawang-merah',
            'unit' => 'kg',
            'is_active' => true,
        ]);
        $market = Market::create(['name' => 'Pasar Gempol Kerep', 'is_active' => true]);
        CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-11', 'price' => 26000, 'source' => 'Uji']);
        CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-12', 'price' => 26500, 'source' => 'Uji']);
        CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-13', 'price' => 27000, 'source' => 'Uji']);
        $run = PredictionRun::create([
            'version' => 'V3_TEST',
            'status' => 'active',
            'data_last_date' => '2026-08-12',
            'activated_at' => now(),
        ]);
        CommodityPrediction::create([
            'prediction_run_id' => $run->id,
            'commodity_id' => $commodity->id,
            'target_date' => '2026-08-19',
            'horizon_days' => 7,
            'predicted_price' => 987654,
            'lower_bound' => 900000,
            'upper_bound' => 1000000,
            'model_name' => 'Model Uji',
            'mae' => 2900,
            'naive_mae' => 1500,
            'beats_naive' => false,
            'rmse' => 3200,
            'mape' => 9.17,
            'smape' => 9.10,
            'accuracy_score' => 90.83,
            'original_status' => 'BELUM LAYAK',
            'normalized_status' => 'BELUM LAYAK',
            'status_reason' => 'Belum mengungguli Naive.',
            'model_version' => 'V3_TEST',
            'data_last_date' => '2026-08-12',
            'model_generated_at' => now(),
        ]);

        $this->get(route('predictions.index', ['commodity' => $commodity->slug, 'horizon' => 7]))
            ->assertOk()
            ->assertSee('Lintasan harga dan prediksi')
            ->assertDontSee('Data aktual lebih baru dari model')
            ->assertDontSee('BATAS MODEL')
            ->assertSee('Rp 27.000')
            ->assertSee('Ditahan')
            ->assertDontSee('Rp 987.654');

        $this->actingAs(User::factory()->create())
            ->get(route('predictions.index', ['commodity' => $commodity->slug, 'horizon' => 7]))
            ->assertOk()
            ->assertSee('Rp 987.654');
    }
}
