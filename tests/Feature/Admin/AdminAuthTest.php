<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Login');
    }

    public function test_guest_is_redirected_from_admin_dashboard_to_login(): void
    {
        $this->app['auth']->forgetGuards();

        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_root_to_login(): void
    {
        $this->app['auth']->forgetGuards();

        $this->get('/')->assertRedirect('/login');
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin_full_access',
        ]);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin_full_access',
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_with_unknown_email_is_rejected(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'ghost@example.com',
            'password' => 'password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->post('/login', [])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_authenticated_user_is_redirected_from_root_to_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);

        $this->actingAs($admin)->get('/')->assertRedirect('/admin/dashboard');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);

        $this->actingAs($admin)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_guest_cannot_access_logout(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }
}