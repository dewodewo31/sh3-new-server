<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\GuestSponsor;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestSponsorAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function quota(Sponsor $sponsor, Event $event, int $max): void
    {
        $event->sponsors()->syncWithPivotValues([$sponsor->id], ['max_guest_accounts' => $max], false);
    }

    private function makeAccount(?int $quota = 2): array
    {
        $sponsor = Sponsor::factory()->create();
        $event = Event::factory()->create();
        if ($quota !== null) {
            $this->quota($sponsor, $event, $quota);
        }

        return [$sponsor, $event];
    }

    public function test_index_requires_admin_full_access(): void
    {
        $this->actingAs($this->user('bendahara'));

        $this->get('/admin/guest-sponsors')->assertForbidden();
    }

    public function test_can_list_guest_sponsors(): void
    {
        $this->actingAs($this->user('admin_full_access'));
        $account = GuestSponsor::factory()->create();

        $this->get('/admin/guest-sponsors')
            ->assertOk()
            ->assertSee($account->user->username);
    }

    public function test_can_set_quota(): void
    {
        $this->actingAs($this->user('admin_full_access'));
        [$sponsor, $event] = $this->makeAccount(null);

        $this->post('/admin/guest-sponsors/quota', [
            'sponsor_id' => $sponsor->id,
            'event_id' => $event->id,
            'max_guest_accounts' => 5,
        ])->assertRedirect();

        $this->assertDatabaseHas('event_sponsors', [
            'sponsor_id' => $sponsor->id,
            'event_id' => $event->id,
            'max_guest_accounts' => 5,
        ]);
    }

    public function test_can_create_account_within_quota(): void
    {
        $this->actingAs($this->user('admin_full_access'));
        [$sponsor, $event] = $this->makeAccount(2);

        $this->post('/admin/guest-sponsors', [
            'sponsor_id' => $sponsor->id,
            'event_id' => $event->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'role' => 'guest_sponsor',
        ]);

        $account = GuestSponsor::first();
        $this->assertNotNull($account);
        $this->assertDatabaseHas('guest_sponsors', [
            'id' => $account->id,
            'sponsor_id' => $sponsor->id,
            'event_id' => $event->id,
            'is_active' => true,
        ]);

        $this->assertStringStartsWith('GS-'.$sponsor->id.'-'.$event->id.'-', $account->qr_code);
    }

    public function test_cannot_create_account_when_quota_unset(): void
    {
        $this->actingAs($this->user('admin_full_access'));
        [$sponsor, $event] = $this->makeAccount(null);

        $this->post('/admin/guest-sponsors', [
            'sponsor_id' => $sponsor->id,
            'event_id' => $event->id,
        ])->assertSessionHasErrors('sponsor_id');

        $this->assertDatabaseCount('guest_sponsors', 0);
    }

    public function test_cannot_create_account_when_quota_full(): void
    {
        $this->actingAs($this->user('admin_full_access'));
        [$sponsor, $event] = $this->makeAccount(1);

        $this->post('/admin/guest-sponsors', [
            'sponsor_id' => $sponsor->id,
            'event_id' => $event->id,
        ])->assertRedirect();

        $this->post('/admin/guest-sponsors', [
            'sponsor_id' => $sponsor->id,
            'event_id' => $event->id,
        ])->assertSessionHasErrors('sponsor_id');

        $this->assertDatabaseCount('guest_sponsors', 1);
    }

    public function test_can_toggle_active(): void
    {
        $this->actingAs($this->user('admin_full_access'));
        $account = GuestSponsor::factory()->create();

        $this->post("/admin/guest-sponsors/{$account->id}/toggle-active")
            ->assertRedirect();

        $this->assertDatabaseHas('guest_sponsors', ['id' => $account->id, 'is_active' => false]);
    }

    public function test_can_update_account(): void
    {
        $this->actingAs($this->user('admin_full_access'));
        $account = GuestSponsor::factory()->create();

        $this->put("/admin/guest-sponsors/{$account->id}", [
            'name' => 'Nama Baru',
            'password' => 'rahasia123',
            'valid_from' => '2026-01-01',
            'valid_until' => '2026-12-31',
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $account->user_id,
            'name' => 'Nama Baru',
        ]);
        $this->assertDatabaseHas('guest_sponsors', [
            'id' => $account->id,
            'valid_until' => '2026-12-31',
        ]);

        $this->assertTrue(password_verify('rahasia123', $account->user->fresh()->password));
    }

    public function test_can_destroy_account_and_user(): void
    {
        $this->actingAs($this->user('admin_full_access'));
        $account = GuestSponsor::factory()->create();

        $this->delete("/admin/guest-sponsors/{$account->id}")->assertRedirect();

        $this->assertDatabaseMissing('guest_sponsors', ['id' => $account->id]);
        $this->assertDatabaseMissing('users', ['id' => $account->user_id]);
    }

    public function test_sponsor_role_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->user('sponsor'));

        $this->get('/admin/sponsors')->assertForbidden();
        $this->get('/admin/guest-sponsors')->assertForbidden();
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_guest_sponsor_role_cannot_login_to_web(): void
    {
        $account = GuestSponsor::factory()->create();

        $this->post('/login', [
            'email' => $account->user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
