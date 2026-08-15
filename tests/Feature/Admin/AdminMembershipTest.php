<?php

namespace Tests\Feature\Admin;

use App\Models\MembershipHistory;
use App\Models\MembershipPlan;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\MembershipPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMembershipTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MembershipPlanSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($this->admin);
    }

    private function makeParticipant(string $name = 'Fajar Hidayat'): Participant
    {
        return Participant::factory()->create([
            'name' => $name,
            'membership_type' => 'none',
        ]);
    }

    public function test_admin_can_view_membership_list(): void
    {
        $participant = $this->makeParticipant('Sari Puspita');
        MembershipHistory::factory()->create([
            'participant_id' => $participant->id,
            'membership_type' => 'tahunan',
            'status' => 'active',
        ]);

        $this->get('/admin/memberships')
            ->assertOk()
            ->assertSee('Sari Puspita');
    }

    public function test_membership_list_shows_stats(): void
    {
        $this->get('/admin/memberships')
            ->assertOk()
            ->assertSee('Membership');
    }

    public function test_admin_can_view_create_membership_page(): void
    {
        $this->makeParticipant('Budi Santoso');

        $this->get('/admin/memberships/create')
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Tahunan');
    }

    public function test_admin_can_grant_membership(): void
    {
        $participant = $this->makeParticipant();

        $this->post('/admin/memberships', [
            'participant_id' => $participant->id,
            'membership_type' => 'tahunan',
        ])->assertRedirect(route('admin.memberships.index'))
            ->assertSessionHas('success', 'Membership berhasil diberikan kepada '.$participant->name);

        $this->assertSame('tahunan', $participant->fresh()->membership_type);

        $this->assertDatabaseHas('membership_histories', [
            'participant_id' => $participant->id,
            'membership_type' => 'tahunan',
            'status' => 'active',
        ]);
    }

    public function test_grant_membership_with_invalid_type_is_rejected(): void
    {
        $participant = $this->makeParticipant();

        $this->post('/admin/memberships', [
            'participant_id' => $participant->id,
            'membership_type' => 'nonexistent_plan',
        ])->assertSessionHasErrors('membership_type');
    }

    public function test_grant_membership_with_inactive_plan_is_rejected(): void
    {
        MembershipPlan::factory()->create([
            'key' => 'mati',
            'name' => 'Plan Nonaktif',
            'is_active' => false,
        ]);

        $participant = $this->makeParticipant();

        $this->post('/admin/memberships', [
            'participant_id' => $participant->id,
            'membership_type' => 'mati',
        ])->assertSessionHasErrors('membership_type');
    }

    public function test_grant_membership_to_active_member_is_rejected(): void
    {
        $participant = $this->makeParticipant('Hendra');
        MembershipHistory::factory()->create([
            'participant_id' => $participant->id,
            'membership_type' => 'tahunan',
            'status' => 'active',
        ]);

        $this->post('/admin/memberships', [
            'participant_id' => $participant->id,
            'membership_type' => 'tahunan',
        ])->assertSessionHasErrors('participant_id');

        $this->assertSame('tahunan', $participant->fresh()->membership_type);
    }

    public function test_admin_can_cancel_membership_history(): void
    {
        $participant = $this->makeParticipant();
        $history = MembershipHistory::factory()->create([
            'participant_id' => $participant->id,
            'membership_type' => 'tahunan',
            'status' => 'active',
        ]);

        $this->post('/admin/memberships/'.$history->id.'/cancel')
            ->assertRedirect(route('admin.memberships.index'))
            ->assertSessionHas('success', 'Membership berhasil dibatalkan');

        $this->assertSame('cancelled', $history->fresh()->status);
    }

    public function test_cancel_nonexistent_membership_returns_404(): void
    {
        $this->post('/admin/memberships/99999/cancel')->assertNotFound();
    }
}