<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CommodityDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_quality_page_displays_database_metrics_and_filters(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seed(CommodityDemoSeeder::class);

        $this->get('/kualitas-data')
            ->assertOk()
            ->assertSee('Kelengkapan per Pasar')
            ->assertSee('Rincian Data');

        $this->get('/kualitas-data?status=zero')
            ->assertOk()
            ->assertSee('Harga Nol');
    }
}
