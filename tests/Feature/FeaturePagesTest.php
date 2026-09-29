<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FeaturePagesTest extends TestCase
{
    use RefreshDatabase;

    public static function featureRoutes(): array
    {
        return [
            'dashboard' => ['/'],
            'harga komoditas' => ['/harga-komoditas'],
            'grafik tren' => ['/grafik-tren'],
            'perbandingan pasar' => ['/perbandingan-pasar'],
            'prediksi harga' => ['/prediksi-harga'],
            'laporan' => ['/laporan'],
            'sinkronisasi' => ['/sinkronisasi-data'],
            'kualitas data' => ['/kualitas-data'],
        ];
    }

    #[DataProvider('featureRoutes')]
    public function test_feature_page_can_be_opened(string $url): void
    {
        if (in_array($url, ['/sinkronisasi-data', '/kualitas-data'], true)) {
            $this->actingAs(User::factory()->create());
        }
        $this->get($url)->assertOk();
    }

    public function test_guest_is_redirected_from_admin_pages_to_login(): void
    {
        $this->get('/master/data-harga')->assertRedirect(route('login'));
        $this->get('/sinkronisasi-data')->assertRedirect(route('login'));
        $this->get('/kualitas-data')->assertRedirect(route('login'));
    }
}
