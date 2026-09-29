<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\CommodityPriceChange;
use App\Models\Market;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_category_can_be_created_updated_and_deleted_when_empty(): void
    {
        $this->post(route('categories.store'), ['name' => 'Bahan Uji'])
            ->assertRedirect(route('categories.index'));

        $category = Category::where('name', 'Bahan Uji')->firstOrFail();
        $this->put(route('categories.update', $category), ['name' => 'Bahan Uji Baru'])
            ->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Bahan Uji Baru']);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_category_with_commodity_is_protected_from_deletion(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);

        $this->from(route('categories.index'))->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_commodity_can_be_created_updated_and_deactivated(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);

        $this->post(route('commodities.store'), [
            'category_id' => $category->id,
            'name' => 'Beras Premium',
            'unit' => 'kg',
            'is_active' => '1',
        ])->assertRedirect(route('commodities.index'));

        $commodity = Commodity::where('name', 'Beras Premium')->firstOrFail();
        $this->put(route('commodities.update', $commodity), [
            'category_id' => $category->id,
            'name' => 'Beras Premium Lokal',
            'unit' => 'Kg',
            'is_active' => '1',
        ])->assertRedirect(route('commodities.index'));
        $this->assertDatabaseHas('commodities', ['id' => $commodity->id, 'name' => 'Beras Premium Lokal']);

        $this->patch(route('commodities.status', $commodity))->assertSessionHas('success');
        $this->assertDatabaseHas('commodities', ['id' => $commodity->id, 'is_active' => false]);
    }

    public function test_master_pages_render(): void
    {
        $this->get(route('categories.index'))->assertOk()->assertSee('Daftar Kategori');
        $this->get(route('commodities.index'))->assertOk()->assertSee('Daftar Komoditas');
        $this->get(route('master-prices.index'))->assertOk()
            ->assertSee('Data Harga Harian')
            ->assertSee('Status perubahan')
            ->assertSee('Terapkan Filter')
            ->assertSee('price-master-actions', false);
    }

    public function test_price_data_can_be_created_updated_and_deleted(): void
    {
        $user = User::factory()->create(['name' => 'Admin Penguji', 'email' => 'admin.penguji@example.test']);
        $this->actingAs($user);
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $commodity = Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Mojosari', 'is_active' => true]);

        $this->post(route('master-prices.store'), ['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-13', 'price' => 15000, 'source' => 'Input manual'])
            ->assertRedirect(route('master-prices.index', ['commodity' => $commodity->id]));
        $price = CommodityPrice::firstOrFail();
        $this->put(route('master-prices.update', $price), ['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-13', 'price' => 15200, 'source' => 'Koreksi manual'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('commodity_prices', ['id' => $price->id, 'price' => 15200]);
        $this->assertDatabaseHas('commodity_price_changes', [
            'commodity_price_id' => $price->id,
            'user_id' => $user->id,
            'user_name' => 'Admin Penguji',
            'old_price' => 15000,
            'new_price' => 15200,
        ]);
        $this->delete(route('master-prices.destroy', $price))->assertSessionHas('success');
        $this->assertSoftDeleted('commodity_prices', ['id' => $price->id]);
        $this->get(route('master-prices.trash'))->assertOk()->assertSee('Beras Premium');
        $this->patch(route('master-prices.restore', $price->id))->assertSessionHas('success');
        $this->assertNotSoftDeleted('commodity_prices', ['id' => $price->id]);
    }

    public function test_archiving_commodity_keeps_its_price_history(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $commodity = Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Mojosari', 'is_active' => true]);
        CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-13', 'price' => 15000, 'source' => 'Uji']);

        $this->delete(route('commodities.destroy', $commodity))->assertSessionHas('success');
        $this->assertSoftDeleted('commodities', ['id' => $commodity->id]);
        $this->assertDatabaseCount('commodity_prices', 1);
    }

    public function test_prices_can_be_deleted_in_bulk_by_selection_or_filter(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $commodity = Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Mojosari', 'is_active' => true]);
        $first = CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-13', 'price' => 15000, 'source' => 'Uji']);
        CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-14', 'price' => 15100, 'source' => 'Uji']);

        $this->delete(route('master-prices.bulk-destroy'), ['scope' => 'selected', 'ids' => [$first->id]])
            ->assertSessionHas('success');
        $this->assertSame(1, CommodityPrice::count());
        $this->assertSame(1, CommodityPrice::onlyTrashed()->count());

        $this->delete(route('master-prices.bulk-destroy'), ['scope' => 'filtered', 'market' => $market->id, 'date' => '2026-08-14'])
            ->assertSessionHas('success');
        $this->assertSame(0, CommodityPrice::count());
        $this->assertSame(2, CommodityPrice::onlyTrashed()->count());
    }

    public function test_archived_price_can_be_permanently_deleted(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $commodity = Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Mojosari', 'is_active' => true]);
        $price = CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-13', 'price' => 15000, 'source' => 'Uji']);
        $price->delete();

        $this->delete(route('master-prices.force-destroy', $price->id))->assertSessionHas('success');
        $this->assertDatabaseMissing('commodity_prices', ['id' => $price->id]);
    }

    public function test_all_archived_prices_can_be_permanently_deleted_without_touching_active_prices(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $commodity = Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Mojosari', 'is_active' => true]);
        $archivedFirst = CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-11', 'price' => 14900, 'source' => 'Uji']);
        $archivedSecond = CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-12', 'price' => 15000, 'source' => 'Uji']);
        $active = CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-13', 'price' => 15100, 'source' => 'Uji']);
        $archivedFirst->delete();
        $archivedSecond->delete();

        $this->get(route('master-prices.trash'))
            ->assertOk()
            ->assertSee('Hapus Semua Permanen')
            ->assertSee('2 data');

        $this->delete(route('master-prices.purge-trash'))
            ->assertRedirect(route('master-prices.trash'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('commodity_prices', ['id' => $archivedFirst->id]);
        $this->assertDatabaseMissing('commodity_prices', ['id' => $archivedSecond->id]);
        $this->assertDatabaseHas('commodity_prices', ['id' => $active->id, 'deleted_at' => null]);
    }

    public function test_bulk_delete_all_requires_a_filter(): void
    {
        $this->from(route('master-prices.index'))->delete(route('master-prices.bulk-destroy'), ['scope' => 'filtered'])
            ->assertRedirect(route('master-prices.index'))->assertSessionHas('error');
    }

    public function test_prices_can_be_filtered_by_original_or_edited_status(): void
    {
        $category = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $commodity = Commodity::create(['category_id' => $category->id, 'name' => 'Beras Premium', 'slug' => 'beras-premium', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Pasar Mojosari', 'is_active' => true]);
        $original = CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-13', 'price' => 15000, 'source' => 'SUMBER-ASLI-UNIK']);
        $edited = CommodityPrice::create(['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => '2026-08-14', 'price' => 15200, 'source' => 'SUMBER-DIUBAH-UNIK']);

        CommodityPriceChange::create([
            'commodity_price_id' => $edited->id,
            'user_name' => 'Admin Penguji',
            'old_price' => 15100,
            'new_price' => 15200,
            'changes' => ['price' => ['from' => 15100, 'to' => 15200]],
        ]);

        $this->get(route('master-prices.index', ['change_status' => 'original']))
            ->assertOk()
            ->assertSee('1 data sesuai filter')
            ->assertSee('SUMBER-ASLI-UNIK')
            ->assertDontSee('SUMBER-DIUBAH-UNIK');

        $this->get(route('master-prices.index', ['change_status' => 'edited']))
            ->assertOk()
            ->assertSee('1 data sesuai filter')
            ->assertSee('SUMBER-DIUBAH-UNIK')
            ->assertDontSee('SUMBER-ASLI-UNIK');

        $this->delete(route('master-prices.bulk-destroy'), ['scope' => 'filtered', 'change_status' => 'edited'])
            ->assertSessionHas('success');
        $this->assertNotSoftDeleted('commodity_prices', ['id' => $original->id]);
        $this->assertSoftDeleted('commodity_prices', ['id' => $edited->id]);
    }
}
