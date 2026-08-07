<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private array $registerPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerPayload = [
            'name' => 'Budi Santoso',
            'username' => 'budi_santoso',
            'email' => 'budi@example.com',
            'phone' => '08123456789',
            'gender' => 'male',
            'date_of_birth' => '2000-01-01',
            'address' => 'Jl. Merdeka No. 1',
            'emergency_contact' => 'Siti',
            'emergency_phone' => '08987654321',
            'medical_conditions' => null,
            'blood_type' => 'O',
            'jersey_size' => 'L',
        ];
    }

    public function test_register_creates_user_and_participant_with_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload);

        $response->assertCreated()
            ->assertJsonPath('message', 'Registrasi berhasil')
            ->assertJsonPath('user.role', 'participant')
            ->assertJsonCount(1, 'user.participants')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'username' => 'budi_santoso',
            'role' => 'participant',
        ]);
        $this->assertDatabaseHas('participants', [
            'email' => 'budi@example.com',
            'phone' => '08123456789',
            'jersey_size' => 'L',
        ]);
    }

    public function test_register_generates_username_when_not_provided(): void
    {
        $payload = $this->registerPayload;
        unset($payload['username']);

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'username' => 'budi_santoso',
            'role' => 'participant',
        ]);
    }

    public function test_register_with_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $this->postJson('/api/v1/auth/register', $this->registerPayload)
            ->assertUnprocessable();
    }

    public function test_register_with_duplicate_username_is_rejected(): void
    {
        User::factory()->create(['username' => 'budi_santoso']);

        $this->postJson('/api/v1/auth/register', $this->registerPayload)
            ->assertUnprocessable();
    }

    public function test_login_success_returns_token(): void
    {
        $user = User::factory()->create([
            'username' => 'johndoe',
            'password' => Hash::make('password'),
            'role' => 'participant',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'johndoe',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Login berhasil')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['token']);
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'username' => 'johndoe',
            'password' => Hash::make('password'),
            'role' => 'participant',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'username' => 'johndoe',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.username.0', 'Username atau password salah.');
    }

    public function test_login_with_nonexistent_username_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'username' => 'nonexistent',
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.username.0', 'Username atau password salah.');
    }

    public function test_login_rejects_admin_users(): void
    {
        User::factory()->create([
            'username' => 'admin_full',
            'password' => Hash::make('password'),
            'role' => 'admin_full_access',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'username' => 'admin_full',
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.username.0', 'Akun ini bukan peserta.');
    }

    public function test_login_inactive_account_is_rejected(): void
    {
        User::factory()->create([
            'username' => 'johndoe',
            'password' => Hash::make('password'),
            'role' => 'participant',
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'username' => 'johndoe',
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.username.0', 'Akun Anda telah dinonaktifkan.');
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create([
            'role' => 'participant',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil');

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create([
            'role' => 'participant',
        ]);
        Participant::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonCount(1, 'user.participants');
    }

    public function test_refresh_rotates_token(): void
    {
        $user = User::factory()->create([
            'role' => 'participant',
        ]);

        $oldToken = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonPath('message', 'Token berhasil diperbarui.')
            ->assertJsonStructure(['token']);

        $newToken = $response->json('token');

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->getJson('/api/v1/auth/me')->assertUnauthorized();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$newToken)
            ->getJson('/api/v1/auth/me')->assertOk();
    }

    public function test_refresh_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/refresh')->assertUnauthorized();
    }

    public function test_forgot_password_sends_reset_link(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'budi@example.com',
            'role' => 'participant',
        ]);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'budi@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'Link reset password telah dikirim ke email Anda.');
    }

    public function test_forgot_password_with_unknown_email_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown@example.com'])
            ->assertUnprocessable();
    }

    public function test_reset_password_with_valid_token_changes_password(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'role' => 'participant',
        ]);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'budi@example.com',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertOk()
            ->assertJsonPath('message', 'Password berhasil direset.');

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
    }

    public function test_reset_password_with_invalid_token_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'budi@example.com',
            'role' => 'participant',
        ]);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'budi@example.com',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertUnprocessable();
    }
}
