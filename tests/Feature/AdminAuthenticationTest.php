<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_username_and_logout(): void
    {
        $user = User::factory()->create(['username' => 'admin_uji', 'password' => Hash::make('Rahasia123!')]);

        $this->post(route('login.store'), ['login' => 'admin_uji', 'password' => 'Rahasia123!'])
            ->assertRedirect(route('master-prices.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_or_invalid_admin_cannot_login(): void
    {
        User::factory()->create(['username' => 'admin_mati', 'password' => Hash::make('Rahasia123!'), 'is_active' => false]);
        $this->post(route('login.store'), ['login' => 'admin_mati', 'password' => 'Rahasia123!'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_authenticated_admin_can_change_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('PasswordLama1!')]);
        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'PasswordLama1!',
            'password' => 'PasswordBaru2!',
            'password_confirmation' => 'PasswordBaru2!',
        ])->assertSessionHas('success');
        $this->assertTrue(Hash::check('PasswordBaru2!', $user->fresh()->password));
    }
}
