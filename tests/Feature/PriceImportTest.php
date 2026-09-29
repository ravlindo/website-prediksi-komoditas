<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\Market;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PriceImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_csv_is_previewed_then_imported_after_confirmation(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        Market::create(['name' => 'Pasar Mojosari', 'is_active' => true]);
        $csv = "tanggal,pasar,kategori,komoditas,satuan,harga\n2026-08-13,Pasar Mojosari,Beras,Beras Premium,kg,15100\n";

        $response = $this->post(route('price-import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('harga.csv', $csv),
        ])->assertOk()->assertSee('Data baru')->assertSee('Rp 15.100');

        $this->assertDatabaseCount('commodity_prices', 0);
        preg_match('/name="token" value="([^"]+)"/', $response->getContent(), $matches);
        $token = $matches[1];

        $this->post(route('price-import.store'), ['token' => $token, 'duplicate_mode' => 'update'])
            ->assertRedirect(route('master-prices.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('commodity_prices', ['price_date' => '2026-08-13', 'price' => 15100]);

        File::deleteDirectory(storage_path("app/private/price-imports/{$token}"));
    }

    public function test_import_page_can_be_opened(): void
    {
        $this->get(route('price-import.create'))->assertOk()->assertSee('Import Data Harga');
    }

    public function test_downloaded_xlsx_template_can_be_read_by_the_importer(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Mojosari', 'is_active' => true]);

        $download = $this->get(route('price-import.template', ['market' => $market->id]))
            ->assertOk()->assertDownload();
        $path = $download->baseResponse->getFile()->getPathname();
        $this->assertFileExists($path);
        $this->assertSame('PK', substr((string) file_get_contents($path), 0, 2));

        $preview = $this->post(route('price-import.preview'), [
            'file' => new UploadedFile($path, 'template.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertOk()->assertSee('Beras Premium')->assertSee('Pasar Mojosari')->assertSee('Rp 0');

        preg_match('/name="token" value="([^"]+)"/', $preview->getContent(), $matches);
        File::deleteDirectory(storage_path("app/private/price-imports/{$matches[1]}"));
    }

    public function test_nama_komoditas_header_is_supported(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        Market::create(['name' => 'Pasar Gempol Kerep', 'is_active' => true]);
        $csv = "Tanggal,Pasar,Nama Komoditas,Kategori,Satuan,Harga Kemarin,Harga Sekarang\n46247,Pasar Gempol Kerep,Beras Premium,Beras,kg,14500,14500\n";

        $response = $this->post(route('price-import.preview'), ['file' => UploadedFile::fake()->createWithContent('harga.csv', $csv)])
            ->assertOk()->assertSee('Beras Premium')->assertSee('Rp 14.500');
        preg_match('/name="token" value="([^"]+)"/', $response->getContent(), $matches);
        File::deleteDirectory(storage_path("app/private/price-imports/{$matches[1]}"));
    }

    public function test_market_name_with_optional_pasar_prefix_is_supported(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        Market::create(['name' => 'Raya Mojosari', 'is_active' => true]);
        $csv = "tanggal,pasar,komoditas,satuan,harga\n2026-08-13,Pasar Raya Mojosari,Beras Premium,kg,14500\n";

        $response = $this->post(route('price-import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('harga.csv', $csv),
        ])->assertOk()->assertSee('Raya Mojosari')->assertSee('Rp 14.500');

        preg_match('/name="token" value="([^"]+)"/', $response->getContent(), $matches);
        File::deleteDirectory(storage_path("app/private/price-imports/{$matches[1]}"));
    }
}
