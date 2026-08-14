<?php

namespace Tests\Feature;

use App\Models\MembershipHistory;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\MembershipPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMembershipEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($admin);

        return $admin;
    }

    private function makeParticipant(string $name): Participant
    {
        return Participant::factory()->create(['name' => $name, 'membership_type' => 'none']);
    }

    private function makeHistory(Participant $participant, string $status, ?string $endDate = null): MembershipHistory
    {
        return MembershipHistory::create([
            'participant_id' => $participant->id,
            'membership_type' => 'tahunan',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => $endDate ?? now()->addMonth()->toDateString(),
            'price' => 1192500,
            'status' => $status,
        ]);
    }

    public function test_non_member_appears_in_dropdown(): void
    {
        $this->admin();
        $fajar = $this->makeParticipant('Fajar Hidayat');

        $this->get('/admin/memberships/create')
            ->assertOk()
            ->assertSee($fajar->name);
    }

    public function test_active_membership_does_not_appear(): void
    {
        $this->admin();
        $hendra = $this->makeParticipant('Hendra Gunawan');
        $this->makeHistory($hendra, MembershipHistory::STATUS_ACTIVE, now()->addMonth()->toDateString());

        $this->get('/admin/memberships/create')
            ->assertOk()
            ->assertDontSee($hendra->name);
    }

    public function test_cancelled_membership_appears(): void
    {
        $this->admin();
        $rizky = $this->makeParticipant('Rizky Pratama');
        $this->makeHistory($rizky, MembershipHistory::STATUS_CANCELLED);

        $this->get('/admin/memberships/create')
            ->assertOk()
            ->assertSee($rizky->name);
    }

    public function test_expired_membership_appears(): void
    {
        $this->admin();
        $budi = $this->makeParticipant('Budi Santoso');
        $this->makeHistory($budi, MembershipHistory::STATUS_EXPIRED, now()->subDay()->toDateString());

        $this->get('/admin/memberships/create')
            ->assertOk()
            ->assertSee($budi->name);
    }

    public function test_active_with_past_end_date_appears(): void
    {
        $this->admin();
        $dani = $this->makeParticipant('Dani');
        $this->makeHistory($dani, MembershipHistory::STATUS_ACTIVE, now()->subDay()->toDateString());

        $this->get('/admin/memberships/create')
            ->assertOk()
            ->assertSee($dani->name);
    }

    public function test_multiple_histories_with_one_active_does_not_appear(): void
    {
        $this->admin();
        $andi = $this->makeParticipant('Andi');
        $this->makeHistory($andi, MembershipHistory::STATUS_EXPIRED, now()->subDay()->toDateString());
        $this->makeHistory($andi, MembershipHistory::STATUS_CANCELLED);
        $this->makeHistory($andi, MembershipHistory::STATUS_ACTIVE, now()->addMonth()->toDateString());

        $this->get('/admin/memberships/create')
            ->assertOk()
            ->assertDontSee($andi->name);
    }

    public function test_multiple_histories_without_active_appears(): void
    {
        $this->admin();
        $andi = $this->makeParticipant('Andi');
        $this->makeHistory($andi, MembershipHistory::STATUS_EXPIRED, now()->subDay()->toDateString());
        $this->makeHistory($andi, MembershipHistory::STATUS_CANCELLED);

        $this->get('/admin/memberships/create')
            ->assertOk()
            ->assertSee($andi->name);
    }

    public function test_post_rejects_active_member(): void
    {
        $this->seed(MembershipPlanSeeder::class);
        $this->admin();
        $hendra = $this->makeParticipant('Hendra Gunawan');
        $this->makeHistory($hendra, MembershipHistory::STATUS_ACTIVE, now()->addMonth()->toDateString());

        $this->post('/admin/memberships', [
            'participant_id' => $hendra->id,
            'membership_type' => 'tahunan',
        ])->assertSessionHasErrors('participant_id');

        $this->assertSame('none', $hendra->fresh()->membership_type);
    }

    public function test_post_allows_eligible_member(): void
    {
        $this->seed(MembershipPlanSeeder::class);
        $this->admin();
        $fajar = $this->makeParticipant('Fajar Hidayat');

        $this->post('/admin/memberships', [
            'participant_id' => $fajar->id,
            'membership_type' => 'tahunan',
        ])->assertRedirect(route('admin.memberships.index'));

        $this->assertSame('tahunan', $fajar->fresh()->membership_type);
    }
}