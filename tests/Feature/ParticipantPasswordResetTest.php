<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParticipantPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeParticipant(string $password = 'oldpass123'): Participant
    {
        $user = User::factory()->create([
            'username' => 'user_'.uniqid(),
            'role' => 'participant',
            'password' => Hash::make($password),
        ]);

        return Participant::factory()->create([
            'user_id' => $user->id,
            'name' => 'Tester',
        ]);
    }

    public function test_verify_with_valid_username_and_hash_id(): void
    {
        $p = $this->makeParticipant();

        $this->postJson('/api/v1/participant/auth/verify-reset', [
            'username' => $p->user->username,
            'hash_id' => $p->hash_id,
        ])->assertOk()
            ->assertJson(['success' => true, 'can_reset' => true]);
    }

    public function test_verify_with_valid_username_but_wrong_hash_id(): void
    {
        $p = $this->makeParticipant();

        $this->postJson('/api/v1/participant/auth/verify-reset', [
            'username' => $p->user->username,
            'hash_id' => '9999',
        ])->assertOk()
            ->assertJson(['success' => false, 'message' => 'Data participant tidak valid.']);
    }

    public function test_verify_with_wrong_username_but_valid_hash_id(): void
    {
        $p = $this->makeParticipant();

        $this->postJson('/api/v1/participant/auth/verify-reset', [
            'username' => 'nonexistent_user',
            'hash_id' => $p->hash_id,
        ])->assertOk()
            ->assertJson(['success' => false, 'message' => 'Data participant tidak valid.']);
    }

    public function test_reset_changes_password_and_login_works_afterwards(): void
    {
        $p = $this->makeParticipant('oldpass123');
        $username = $p->user->username;

        $this->postJson('/api/v1/participant/auth/reset-password', [
            'username' => $username,
            'hash_id' => $p->hash_id,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertOk()
            ->assertJson(['success' => true, 'message' => 'Password berhasil diperbarui.']);

        // Old password no longer works.
        $this->postJson('/api/v1/auth/login', [
            'username' => $username,
            'password' => 'oldpass123',
        ])->assertStatus(422);

        // New password works.
        $this->postJson('/api/v1/auth/login', [
            'username' => $username,
            'password' => 'newpass123',
        ])->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_reset_stores_password_using_hash_make(): void
    {
        $p = $this->makeParticipant('oldpass123');

        $this->postJson('/api/v1/participant/auth/reset-password', [
            'username' => $p->user->username,
            'hash_id' => $p->hash_id,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertOk();

        $user = $p->user->fresh();
        $this->assertNotEquals('newpass123', $user->password);
        $this->assertTrue(Hash::check('newpass123', $user->password));
    }

    public function test_reset_endpoint_is_rate_limited(): void
    {
        // Unique IP so the bucket is fresh and deterministic.
        $server = ['REMOTE_ADDR' => '203.0.113.99'];

        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/v1/participant/auth/reset-password', [
                'username' => 'someone',
                'hash_id' => '9999',
                'password' => 'short',
                'password_confirmation' => 'short',
            ], $server)->assertStatus(422);
        }

        $this->postJson('/api/v1/participant/auth/reset-password', [
            'username' => 'someone',
            'hash_id' => '9999',
            'password' => 'short',
            'password_confirmation' => 'short',
        ], $server)->assertStatus(429);
    }

    public function test_reset_validation_error_when_fields_missing(): void
    {
        $this->postJson('/api/v1/participant/auth/reset-password', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'hash_id', 'password']);
    }

    public function test_reset_validation_error_when_confirmation_mismatch(): void
    {
        $p = $this->makeParticipant();

        $this->postJson('/api/v1/participant/auth/reset-password', [
            'username' => $p->user->username,
            'hash_id' => $p->hash_id,
            'password' => 'newpass123',
            'password_confirmation' => 'different123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_admin_cannot_use_participant_endpoint(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin_'.uniqid(),
            'role' => 'admin_full_access',
        ]);

        $this->postJson('/api/v1/participant/auth/verify-reset', [
            'username' => $admin->username,
            'hash_id' => '9999',
        ])->assertOk()
            ->assertJson(['success' => false, 'message' => 'Data participant tidak valid.']);

        $this->postJson('/api/v1/participant/auth/reset-password', [
            'username' => $admin->username,
            'hash_id' => '9999',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertOk()
            ->assertJson(['success' => false, 'message' => 'Data participant tidak valid.']);
    }

    public function test_participant_cannot_reset_another_participants_account(): void
    {
        $a = $this->makeParticipant();
        $b = $this->makeParticipant();

        // A's username with B's hash_id must be rejected.
        $this->postJson('/api/v1/participant/auth/reset-password', [
            'username' => $a->user->username,
            'hash_id' => $b->hash_id,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertOk()
            ->assertJson(['success' => false, 'message' => 'Data participant tidak valid.']);

        // B's password must remain unchanged.
        $this->assertTrue(Hash::check('oldpass123', $b->user->fresh()->password));
    }
}
