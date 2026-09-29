<?php

namespace Tests\Feature;

use App\Jobs\RunAutomaticPrediction;
use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use App\Models\PredictionRun;
use App\Models\User;
use App\Services\PredictionAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomaticPredictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_latest_date_is_queued_for_automatic_prediction(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $commodity = Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Uji', 'is_active' => true]);
        CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-09-01', 'price' => 15000, 'source' => 'Uji']);

        $result = app(PredictionAutomationService::class)->start($user->id);

        $this->assertSame('queued', $result['status']);
        $this->assertDatabaseHas('prediction_runs', [
            'id' => $result['run']->id,
            'status' => 'queued',
            'trigger_type' => 'automatic',
        ]);
        $this->assertSame('2026-09-01', PredictionRun::find($result['run']->id)->data_last_date->toDateString());
        Queue::assertPushed(RunAutomaticPrediction::class, fn ($job) => $job->predictionRunId === $result['run']->id);
    }

    public function test_partial_latest_date_does_not_start_prediction(): void
    {
        Queue::fake();
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $first = Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        Commodity::create(['category_id' => $category->id, 'name' => 'Beras Medium', 'slug' => 'beras-medium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Uji', 'is_active' => true]);
        CommodityPrice::create(['commodity_id' => $first->id, 'market_id' => $market->id, 'price_date' => '2026-09-01', 'price' => 15000, 'source' => 'Uji']);

        $result = app(PredictionAutomationService::class)->start();

        $this->assertSame('incomplete', $result['status']);
        $this->assertDatabaseCount('prediction_runs', 0);
        Queue::assertNothingPushed();
    }
}
