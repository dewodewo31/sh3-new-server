<?php

namespace Tests\Feature\Admin;

use App\Models\MembershipHistory;
use App\Models\MembershipPlan;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMembershipPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($this->admin);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'key' => 'bulanan',
            'name' => 'Bulanan',
            'description' => 'Membership 1 bulan',
            'price' => 100000,
            'base_event_price' => 25000,
            'discount_percentage' => 5,
            'reference_event_count' => 4,
            'duration' => 1,
            'duration_unit' => 'months',
            'is_active' => 1,
            'sort_order' => 1,
        ], $overrides);
    }

    public function test_admin_can_view_plan_list(): void
    {
        MembershipPlan::factory()->create(['name' => 'Tahunan Pro']);

        $this->get('/admin/membership-plans')
            ->assertOk()
            ->assertSee('Tahunan Pro');
    }

    public function test_plan_list_supports_search_filter(): void
    {
        MembershipPlan::factory()->create(['name' => 'Tahunan Pro']);
        MembershipPlan::factory()->create(['name' => 'Mingguan Ekstra']);

        $this->get('/admin/membership-plans?search=Tahunan')
            ->assertOk()
            ->assertSee('Tahunan Pro')
            ->assertDontSee('Mingguan Ekstra');
    }

    public function test_plan_list_supports_status_filter(): void
    {
        MembershipPlan::factory()->create(['name' => 'Aktif Plan', 'is_active' => true]);
        MembershipPlan::factory()->create(['name' => 'Nonaktif Plan', 'is_active' => false]);

        $this->get('/admin/membership-plans?status=inactive')
            ->assertOk()
            ->assertSee('Nonaktif Plan')
            ->assertDontSee('Aktif Plan');
    }

    public function test_admin_can_create_plan(): void
    {
        $this->post('/admin/membership-plans', $this->validPayload())
            ->assertRedirect(route('admin.membership-plans.index'))
            ->assertSessionHas('success', 'Plan membership berhasil dibuat');

        $this->assertDatabaseHas('membership_plans', [
            'key' => 'bulanan',
            'name' => 'Bulanan',
            'price' => 100000,
            'is_active' => true,
        ]);
    }

    public function test_create_plan_with_duplicate_key_is_rejected(): void
    {
        MembershipPlan::factory()->create(['key' => 'bulanan']);

        $this->post('/admin/membership-plans', $this->validPayload())
            ->assertSessionHasErrors('key');
    }

    public function test_create_plan_with_invalid_key_is_rejected(): void
    {
        $this->post('/admin/membership-plans', $this->validPayload([
            'key' => 'Bulanan Inval!d',
        ]))->assertSessionHasErrors('key');
    }

    public function test_admin_can_update_plan(): void
    {
        $plan = MembershipPlan::factory()->create(['name' => 'Old Plan']);

        $this->put('/admin/membership-plans/'.$plan->id, $this->validPayload([
            'key' => $plan->key,
            'name' => 'New Plan',
            'price' => 150000,
        ]))->assertRedirect(route('admin.membership-plans.index'))
            ->assertSessionHas('success', 'Plan membership berhasil diupdate');

        $this->assertDatabaseHas('membership_plans', [
            'id' => $plan->id,
            'name' => 'New Plan',
            'price' => 150000,
        ]);
    }

    public function test_update_plan_is_active_defaults_to_false_when_absent(): void
    {
        $plan = MembershipPlan::factory()->create(['is_active' => true]);

        $this->put('/admin/membership-plans/'.$plan->id, $this->validPayload([
            'key' => $plan->key,
        ]));

        $this->assertFalse($plan->fresh()->is_active);
    }

    public function test_admin_can_delete_unused_plan(): void
    {
        $plan = MembershipPlan::factory()->create();

        $this->delete('/admin/membership-plans/'.$plan->id)
            ->assertRedirect(route('admin.membership-plans.index'))
            ->assertSessionHas('success', 'Plan membership berhasil dihapus');

        $this->assertDatabaseMissing('membership_plans', ['id' => $plan->id]);
    }

    public function test_plan_used_by_history_cannot_be_deleted(): void
    {
        $plan = MembershipPlan::factory()->create(['key' => 'dipakai']);
        MembershipHistory::factory()->create([
            'membership_type' => 'dipakai',
            'status' => 'active',
        ]);

        $this->from('/admin/membership-plans')->delete('/admin/membership-plans/'.$plan->id)
            ->assertRedirect('/admin/membership-plans')
            ->assertSessionHas('error', 'Plan tidak bisa dihapus karena sudah dipakai oleh peserta.');

        $this->assertDatabaseHas('membership_plans', ['id' => $plan->id]);
    }

    public function test_plan_used_by_participant_cannot_be_deleted(): void
    {
        $plan = MembershipPlan::factory()->create(['key' => 'dipakai']);
        Participant::factory()->create(['membership_type' => 'dipakai']);

        $this->from('/admin/membership-plans')->delete('/admin/membership-plans/'.$plan->id)
            ->assertRedirect('/admin/membership-plans')
            ->assertSessionHas('error', 'Plan tidak bisa dihapus karena sudah dipakai oleh peserta.');

        $this->assertDatabaseHas('membership_plans', ['id' => $plan->id]);
    }

    public function test_update_nonexistent_plan_returns_404(): void
    {
        $this->put('/admin/membership-plans/99999', $this->validPayload())->assertNotFound();
    }
}