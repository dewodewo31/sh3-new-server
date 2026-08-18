<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\GuestSponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestSponsorApiTest extends TestCase
{
    use RefreshDatabase;

    private function account(array $overrides = []): GuestSponsor
    {
        return GuestSponsor::factory()->create($overrides);
    }

    private function tokenFor(GuestSponsor $account): string
    {
        return $account->user->createToken('test')->plainTextToken;
    }

    public function test_guest_sponsor_can_login(): void
    {
        $account = $this->account();

        $this->postJson('/api/v1/guest-sponsor/auth/login', [
            'username' => $account->user->username,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'guest_sponsor' => ['id', 'name', 'username', 'sponsor_name', 'event_id', 'event_title', 'qr_code', 'valid_until'],
            ]);
    }

    public function test_guest_sponsor_login_rejects_wrong_password(): void
    {
        $account = $this->account();

        $this->postJson('/api/v1/guest-sponsor/auth/login', [
            'username' => $account->user->username,
            'password' => 'salah',
        ])->assertUnprocessable();
    }

    public function test_guest_sponsor_login_rejected_when_disabled(): void
    {
        $account = $this->account(['is_active' => false]);

        $this->postJson('/api/v1/guest-sponsor/auth/login', [
            'username' => $account->user->username,
            'password' => 'password',
        ])->assertUnprocessable();
    }

    public function test_guest_sponsor_login_rejected_when_expired(): void
    {
        $account = $this->account(['valid_until' => now()->subDay()->toDateString()]);

        $this->postJson('/api/v1/guest-sponsor/auth/login', [
            'username' => $account->user->username,
            'password' => 'password',
        ])->assertUnprocessable();
    }

    public function test_me_returns_guest_sponsor_detail(): void
    {
        $account = $this->account();

        $this->withToken($this->tokenFor($account))
            ->getJson('/api/v1/guest-sponsor/auth/me')
            ->assertOk()
            ->assertJsonPath('data.qr_code', $account->qr_code)
            ->assertJsonPath('data.usable', true);
    }

    public function test_guest_sponsor_can_check_in(): void
    {
        $account = $this->account();

        $this->withToken($this->tokenFor($account))
            ->postJson('/api/v1/guest-sponsor/attendance/check-in', [
                'event_id' => $account->event_id,
                'method' => 'qr_code',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Check-in berhasil');

        $this->assertDatabaseHas('guest_sponsor_attendances', [
            'guest_sponsor_id' => $account->id,
            'event_id' => $account->event_id,
            'status' => 'present',
        ]);
        $this->assertNotNull($account->attendances()->first()->check_in_time);
    }

    public function test_duplicate_check_in_rejected(): void
    {
        $account = $this->account();

        $this->withToken($this->tokenFor($account))
            ->postJson('/api/v1/guest-sponsor/attendance/check-in', ['event_id' => $account->event_id])
            ->assertOk();

        $this->withToken($this->tokenFor($account))
            ->postJson('/api/v1/guest-sponsor/attendance/check-in', ['event_id' => $account->event_id])
            ->assertUnprocessable();
    }

    public function test_check_in_rejected_when_event_completed(): void
    {
        $account = $this->account();
        $account->event->update(['status' => Event::STATUS_COMPLETED]);

        $this->withToken($this->tokenFor($account))
            ->postJson('/api/v1/guest-sponsor/attendance/check-in', ['event_id' => $account->event_id])
            ->assertUnprocessable();
    }

    public function test_guest_sponsor_can_check_out(): void
    {
        $account = $this->account();

        $this->withToken($this->tokenFor($account))
            ->postJson('/api/v1/guest-sponsor/attendance/check-in', ['event_id' => $account->event_id])
            ->assertOk();

        $this->withToken($this->tokenFor($account))
            ->postJson('/api/v1/guest-sponsor/attendance/check-out', ['event_id' => $account->event_id])
            ->assertOk()
            ->assertJsonPath('message', 'Check-out berhasil');

        $this->assertNotNull($account->attendances()->first()->check_out_time);
    }

    public function test_check_out_without_check_in_rejected(): void
    {
        $account = $this->account();

        $this->withToken($this->tokenFor($account))
            ->postJson('/api/v1/guest-sponsor/attendance/check-out', ['event_id' => $account->event_id])
            ->assertUnprocessable();
    }

    public function test_scan_recognizes_qr_code(): void
    {
        $account = $this->account();

        $admin = User::factory()->create(['role' => 'admin_full_access']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/guest-sponsor/attendance/scan', [
                'qr_code' => $account->qr_code,
                'event_id' => $account->event_id,
            ])
            ->assertOk()
            ->assertJsonPath('data.qr_code', $account->qr_code)
            ->assertJsonPath('data.usable', true);
    }

    public function test_scan_rejects_unknown_qr_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/guest-sponsor/attendance/scan', ['qr_code' => 'GS-999-999-9999'])
            ->assertUnprocessable();
    }

    public function test_scan_rejects_wrong_event(): void
    {
        $account = $this->account();
        $otherEvent = Event::factory()->create();

        $admin = User::factory()->create(['role' => 'admin_full_access']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/guest-sponsor/attendance/scan', [
                'qr_code' => $account->qr_code,
                'event_id' => $otherEvent->id,
            ])
            ->assertUnprocessable();
    }

    public function test_participant_cannot_use_guest_sponsor_attendance(): void
    {
        $participant = User::factory()->create(['role' => 'participant']);
        $event = Event::factory()->create();

        $this->actingAs($participant, 'sanctum')
            ->postJson('/api/v1/guest-sponsor/attendance/check-in', ['event_id' => $event->id])
            ->assertForbidden();
    }

    public function test_my_returns_attendance_history(): void
    {
        $account = $this->account();

        $this->withToken($this->tokenFor($account))
            ->postJson('/api/v1/guest-sponsor/attendance/check-in', ['event_id' => $account->event_id])
            ->assertOk();

        $this->withToken($this->tokenFor($account))
            ->getJson('/api/v1/guest-sponsor/attendance/my')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_id', $account->event_id);
    }
}
