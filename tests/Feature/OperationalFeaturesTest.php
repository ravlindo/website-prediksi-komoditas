<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_admin_pages_are_protected_and_render_for_admin(): void
    {
        $this->get(route('activity.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        foreach (['activity.index', 'backups.index', 'master-archive.index', 'admin-predictions.index', 'admin-predictions.create', 'profile.edit'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_report_can_be_exported_as_csv(): void
    {
        $response = $this->get(route('reports.export'));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $lines = preg_split('/\r\n|\n|\r/', $response->streamedContent());
        $this->assertStringContainsString('sep=;', $lines[0]);
        $this->assertCount(9, str_getcsv($lines[1], ';', '"', ''));
    }

    public function test_pdf_report_contains_all_filtered_rows_beyond_one_preview_page(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $commodity = Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Uji', 'is_active' => true]);

        foreach (range(1, 45) as $day) {
            CommodityPrice::create([
                'commodity_id' => $commodity->id,
                'market_id' => $market->id,
                'price_date' => now()->subDays($day)->toDateString(),
                'price' => 14000 + $day,
                'source' => 'Pengujian PDF',
            ]);
        }

        $response = $this->get(route('reports.pdf', ['market' => $market->id]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-report-row-count', '45');
        $this->assertStringContainsString('attachment; filename="laporan-harga-', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_commodity_price_download_exports_the_selected_filters(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $commodity = Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Uji', 'is_active' => true]);
        CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-12', 'price' => 15000, 'source' => 'Uji']);
        $response = $this->get(route('prices.export', ['date' => '2026-08-12', 'market' => $market->id, 'category' => $category->id]));
        $response->assertOk()->assertDownload('harga-komoditas-2026-08-12.csv');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Beras Premium', $content);
        $lines = preg_split('/\r\n|\n|\r/', $content);
        $this->assertStringContainsString('sep=;', $lines[0]);
        $this->assertCount(12, str_getcsv($lines[1], ';', '"', ''));
        $this->assertCount(12, str_getcsv($lines[2], ';', '"', ''));
        $this->assertStringContainsString('Pasar Uji', $content);
    }

    public function test_admin_mutation_is_written_to_activity_log(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Admin Baru', 'username' => 'admin-baru', 'email' => 'baru@example.test'])->assertSessionHas('success');
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'route_name' => 'profile.update', 'method' => 'PUT']);
    }
}
