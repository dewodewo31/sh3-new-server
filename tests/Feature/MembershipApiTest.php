<?php

namespace Tests\Feature;

use App\Models\MembershipHistory;
use App\Models\MembershipPlan;
use App\Models\Participant;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\PaymentService;
use Database\Seeders\MembershipPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MembershipApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Participant $participant;

    private MembershipService $membershipService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MembershipPlanSeeder::class);

        $this->user = User::factory()->create();
        $this->participant = Participant::factory()->create([
            'user_id' => $this->user->id,
            'membership_type' => 'none',
        ]);
        $this->membershipService = app(MembershipService::class);

        Sanctum::actingAs($this->user);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/membership')->assertUnauthorized();
        $this->postJson('/api/v1/membership/subscribe')->assertUnauthorized();
    }

    public function test_membership_status_returns_404_without_participant(): void
    {
        $this->user->participants()->delete();

        $this->getJson('/api/v1/membership')->assertNotFound();
    }

    public function test_can_view_membership_status(): void
    {
        $this->membershipService->grant($this->participant, 'tahunan');

        $response = $this->getJson('/api/v1/membership');

        $response->assertOk()
            ->assertJsonPath('data.membership_type', 'tahunan')
            ->assertJsonPath('data.is_membership_active', true)
            ->assertJsonCount(1, 'data.membership_histories')
            ->assertJsonPath('data.membership_histories.0.status', 'active');
    }

    public function test_can_list_membership_plans(): void
    {
        $response = $this->getJson('/api/v1/membership/plans');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.type', 'tahunan')
            ->assertJsonPath('data.0.price', 400000);
    }

    public function test_can_list_membership_history(): void
    {
        $this->membershipService->grant($this->participant, 'setengah_tahun');

        $this->getJson('/api/v1/membership/history')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.membership_type', 'setengah_tahun');
    }

    public function test_can_subscribe_membership_creates_pending_payment(): void
    {
        $response = $this->postJson('/api/v1/membership/subscribe', [
            'membership_type' => 'tahunan',
            'payment_method' => 'transfer',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.membership_type', 'tahunan')
            ->assertJsonPath('data.status', 'pending');

        $history = $this->participant->membershipHistories()->first();

        $this->assertSame('pending', $history->status);
        $this->assertSame(400000.0, (float) $history->price);
        $this->assertDatabaseHas('payments', [
            'participant_id' => $this->participant->id,
            'payment_type' => 'membership',
            'paymentable_id' => $history->id,
            'paymentable_type' => MembershipHistory::class,
            'status' => 'pending',
            'amount' => 400000.00,
        ]);
    }

    public function test_subscribe_rejects_invalid_membership_type(): void
    {
        $this->postJson('/api/v1/membership/subscribe', [
            'membership_type' => 'invalid',
            'payment_method' => 'transfer',
        ])->assertUnprocessable();
    }

    public function test_confirmed_membership_payment_activates_membership(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara']);

        $history = $this->membershipService->requestSubscription($this->participant, 'tahunan', 'transfer');
        $payment = $history->fresh()->payment;

        $this->assertSame('pending', $history->fresh()->status);

        app(PaymentService::class)->confirmPayment($payment, $bendahara->id);

        $this->assertSame('active', $history->fresh()->status);
        $this->assertSame('tahunan', $this->participant->fresh()->membership_type);
        $this->assertNotNull($this->participant->fresh()->membership_end_date);
    }

    public function test_can_cancel_membership(): void
    {
        $this->membershipService->grant($this->participant, 'mingguan');

        $this->postJson('/api/v1/membership/cancel')
            ->assertOk()
            ->assertJsonPath('message', 'Membership dibatalkan.');

        $this->assertSame('none', $this->participant->fresh()->membership_type);
        $this->assertSame('cancelled', $this->participant->membershipHistories()->first()->status);
    }

    public function test_admin_grant_activates_membership_immediately(): void
    {
        $history = $this->membershipService->grant($this->participant, 'mingguan');

        $this->assertSame('active', $history->status);
        $this->assertSame('mingguan', $this->participant->fresh()->membership_type);
        $this->assertSame(now()->addDays(7)->toDateString(), $this->participant->fresh()->membership_end_date->toDateString());
    }

    public function test_admin_can_access_membership_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($admin);

        $this->get('/admin/memberships')
            ->assertOk()
            ->assertSee('Riwayat Membership');
    }

    public function test_admin_can_grant_membership_via_web(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($admin);

        $this->post('/admin/memberships', [
            'participant_id' => $this->participant->id,
            'membership_type' => 'tahunan',
        ])->assertRedirect(route('admin.memberships.index'));

        $this->assertSame('tahunan', $this->participant->fresh()->membership_type);
        $this->assertDatabaseHas('membership_histories', [
            'participant_id' => $this->participant->id,
            'membership_type' => 'tahunan',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_customize_plan_price_and_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($admin);

        $plan = MembershipPlan::where('key', 'tahunan')->firstOrFail();

        $this->put('/admin/membership-plans/'.$plan->id, [
            'key' => $plan->key,
            'name' => 'Premium Tahunan',
            'price' => 500000,
            'duration' => 12,
            'duration_unit' => 'months',
            'sort_order' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('admin.membership-plans.index'));

        $plan->refresh();
        $this->assertSame('Premium Tahunan', $plan->name);
        $this->assertSame(500000, $plan->price);

        $this->assertSame(500000, $this->membershipService->calculatePrice('tahunan'));
    }

    public function test_admin_can_create_and_delete_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($admin);

        $this->post('/admin/membership-plans', [
            'key' => 'dua_bulan',
            'name' => 'Dua Bulan',
            'price' => 75000,
            'duration' => 2,
            'duration_unit' => 'months',
            'sort_order' => 4,
            'is_active' => 1,
        ])->assertRedirect(route('admin.membership-plans.index'));

        $this->assertDatabaseHas('membership_plans', [
            'key' => 'dua_bulan',
            'name' => 'Dua Bulan',
            'price' => 75000,
            'duration' => 2,
            'duration_unit' => 'months',
        ]);

        $plan = MembershipPlan::where('key', 'dua_bulan')->firstOrFail();

        $this->delete('/admin/membership-plans/'.$plan->id)
            ->assertRedirect(route('admin.membership-plans.index'));

        $this->assertDatabaseMissing('membership_plans', ['id' => $plan->id]);
    }

    public function test_plan_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($admin);

        $this->membershipService->grant($this->participant, 'tahunan');

        $plan = MembershipPlan::where('key', 'tahunan')->firstOrFail();

        $this->delete('/admin/membership-plans/'.$plan->id)
            ->assertRedirect(route('admin.membership-plans.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('membership_plans', ['id' => $plan->id]);
    }
}
