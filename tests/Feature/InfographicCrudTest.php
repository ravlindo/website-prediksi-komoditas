<?php

namespace Tests\Feature;

use App\Models\Infographic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InfographicCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_gallery_only_displays_published_infographics(): void
    {
        Infographic::create(['title' => 'Harga Beras', 'slug' => 'harga-beras', 'agency' => 'DINAS PERINDUSTRIAN DAN PERDAGANGAN', 'image_path' => 'infographics/beras.jpg', 'is_published' => true]);
        Infographic::create(['title' => 'Konten Rahasia', 'slug' => 'konten-rahasia', 'agency' => 'DINAS PERINDUSTRIAN DAN PERDAGANGAN', 'image_path' => 'infographics/rahasia.jpg', 'is_published' => false]);

        $this->get(route('infographics.index'))
            ->assertOk()
            ->assertSee('Harga Beras')
            ->assertSee('data-infographic-lightbox', false)
            ->assertDontSee('Konten Rahasia');
    }

    public function test_guest_cannot_manage_infographics(): void
    {
        $this->get(route('admin-infographics.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_update_toggle_and_delete_infographic(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $this->post(route('admin-infographics.store'), [
            'title' => 'Harga Bahan Pokok Agustus',
            'agency' => 'DINAS PERINDUSTRIAN DAN PERDAGANGAN',
            'publication_year' => 2026,
            'sort_order' => 1,
            'is_published' => 1,
            'image' => UploadedFile::fake()->create('infografis.jpg', 500, 'image/jpeg'),
        ])->assertRedirect(route('admin-infographics.index'));

        $item = Infographic::firstOrFail();
        Storage::disk('public')->assertExists($item->image_path);
        $this->assertSame($admin->id, $item->created_by);

        $this->put(route('admin-infographics.update', $item), [
            'title' => 'Harga Bahan Pokok September',
            'agency' => 'DINAS PERINDUSTRIAN DAN PERDAGANGAN',
            'publication_year' => 2026,
            'sort_order' => 2,
            'is_published' => 1,
        ])->assertRedirect(route('admin-infographics.index'));
        $this->assertDatabaseHas('infographics', ['id' => $item->id, 'title' => 'Harga Bahan Pokok September']);

        $this->patch(route('admin-infographics.status', $item))->assertSessionHas('success');
        $this->assertDatabaseHas('infographics', ['id' => $item->id, 'is_published' => false]);

        $path = $item->image_path;
        $this->delete(route('admin-infographics.destroy', $item))->assertSessionHas('success');
        $this->assertSoftDeleted('infographics', ['id' => $item->id]);
        Storage::disk('public')->assertExists($path);
    }
}
