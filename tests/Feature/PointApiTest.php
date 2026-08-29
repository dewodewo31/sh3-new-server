<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\MembershipHistory;
use App\Models\MembershipPlan;
use App\Models\Merchandise;
use App\Models\MerchandiseOrder;
use App\Models\Participant;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PointApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Participant $participant;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->participant = Participant::factory()->create(['user_id' => $this->user->id]);

        $this->category = Category::create([
            'name' => 'Long Run',
            'slug' => 'long-run',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);
    }

    private function createEvent(): Event
    {
        return Event::create([
            'category_id' => $this->category->id,
            'title' => 'Long Run Points Test',
            'description' => 'Deskripsi',
            'location' => 'Monas',
            'address' => 'Jakarta',
            'start_date' => now()->addDays(10)->setTime(6, 0),
            'end_date' => now()->addDays(10)->setTime(9, 0),
            'registration_start_date' => now()->subDays(5),
            'registration_end_date' => now()->addDays(5),
            'quota' => 10,
            'price' => 0,
            'is_free_for_members' => true,
            'status' => 'publish',
        ]);
    }

    private function register(Event $event, Participant $participant, string $qr = null): EventParticipant
    {
        return EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'free',
            'amount' => 0,
            'payment_status' => 'confirmed',
            // qr_code is globally UNIQUE; must differ per registration.
            'qr_code' => $qr ?? ($participant->hash_id.'-EV'.$event->id),
        ]);
    }

    /** Create a fresh event AND register the participant, returning the event. */
    private function createEventAndRegister(Participant $participant): Event
    {
        $event = $this->createEvent();
        $this->register($event, $participant);

        return $event;
    }

    private function checkIn(Participant $participant, Event $event): void
    {
        $this->postJson('/api/v1/attendance/check-in', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'method' => 'qr_code',
        ])->assertOk();
    }

    private function giveActiveMembership(Participant $participant, MembershipPlan $plan): void
    {
        MembershipHistory::create([
            'participant_id' => $participant->id,
            'membership_type' => $plan->key,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(12)->toDateString(),
            'status' => MembershipHistory::STATUS_ACTIVE,
            'price' => $plan->price,
        ]);
    }

    public function test_member_with_rate_earns_points_on_check_in(): void
    {
        $plan = MembershipPlan::factory()->create(['point_per_event_checkin' => 50]);
        $this->giveActiveMembership($this->participant, $plan);

        $event = $this->createEvent();
        $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/check-in', [
            'event_id' => $event->id,
            'participant_id' => $this->participant->id,
            'method' => 'qr_code',
        ])->assertOk();

        $this->assertSame(50, $this->participant->fresh()->point_balance);
        $this->assertDatabaseHas('point_transactions', [
            'participant_id' => $this->participant->id,
            'type' => PointTransaction::TYPE_EARN,
            'amount' => 50,
            'point_rate' => 50,
        ]);
    }

    public function test_check_in_is_idempotent_one_earn_per_attendance(): void
    {
        $plan = MembershipPlan::factory()->create(['point_per_event_checkin' => 50]);
        $this->giveActiveMembership($this->participant, $plan);

        $event = $this->createEvent();
        $this->register($event, $this->participant);

        // First check-in succeeds.
        $this->postJson('/api/v1/attendance/check-in', [
            'event_id' => $event->id,
            'participant_id' => $this->participant->id,
            'method' => 'qr_code',
        ])->assertOk();

        // Calling earn twice via the service only ever yields one EARN.
        $attendance = Attendance::first();
        $service = app(PointService::class);
        $service->earnForAttendance($attendance, $event, $this->participant);
        $service->earnForAttendance($attendance, $event, $this->participant);

        $this->assertSame(50, $this->participant->fresh()->point_balance);
        $this->assertSame(1, PointTransaction::where('type', PointTransaction::TYPE_EARN)->count());
    }

    public function test_non_member_earns_zero_with_default_rate(): void
    {
        config(['points.non_member_rate' => 0]);

        $event = $this->createEvent();
        $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/check-in', [
            'event_id' => $event->id,
            'participant_id' => $this->participant->id,
            'method' => 'qr_code',
        ])->assertOk();

        $this->assertSame(0, $this->participant->fresh()->point_balance);
        $this->assertSame(0, PointTransaction::count());
    }

    public function test_ots_aggregator_never_earns(): void
    {
        $plan = MembershipPlan::factory()->create(['point_per_event_checkin' => 50]);
        $ots = Participant::factory()->create(['hash_id' => Participant::OTS_AGGREGATOR_CODE]);
        $this->giveActiveMembership($ots, $plan);

        $event = $this->createEvent();
        $this->register($event, $ots);

        $service = app(PointService::class);
        $attendance = Attendance::create([
            'event_participant_id' => EventParticipant::first()->id,
            'status' => 'present',
            'check_in_time' => now(),
        ]);

        $service->earnForAttendance($attendance, $event, $ots);

        $this->assertSame(0, $ots->fresh()->point_balance);
        $this->assertSame(0, PointTransaction::count());
    }

    public function test_ots_registration_never_earns(): void
    {
        $plan = MembershipPlan::factory()->create(['point_per_event_checkin' => 50]);
        $this->giveActiveMembership($this->participant, $plan);

        $event = $this->createEvent();
        // OTS registration is identified by a QR prefix OTS-.
        $registration = $this->register($event, $this->participant, 'OTS-'.strtoupper(substr($this->participant->hash_id, 0, 6)).'EV'.$event->id);

        $attendance = Attendance::create([
            'event_participant_id' => $registration->id,
            'status' => 'present',
            'check_in_time' => now(),
        ]);

        $service = app(PointService::class);
        $service->earnForAttendance($attendance, $event, $this->participant);

        $this->assertSame(0, $this->participant->fresh()->point_balance);
        $this->assertSame(0, PointTransaction::count());
    }

    public function test_redemption_deducts_points_and_sets_snapshots(): void
    {
        $plan = MembershipPlan::factory()->create(['point_per_event_checkin' => 100]);
        $this->giveActiveMembership($this->participant, $plan);
        $event = $this->createEventAndRegister($this->participant);
        $this->checkIn($this->participant, $event);

        // Earn twice via service to have 200 points.
        $service = app(PointService::class);
        // create a second attendance for a second event (capture real EP row)
        $event2 = $this->createEvent();
        $reg2 = $this->register($event2, $this->participant, 'qr-ep2-'.$event2->id);
        $att2 = Attendance::create(['event_participant_id' => $reg2->id, 'status' => 'present', 'check_in_time' => now()]);
        $service->earnForAttendance($att2, $event2, $this->participant);
        $this->assertSame(200, $this->participant->fresh()->point_balance);

        $merch = Merchandise::factory()->create([
            'price' => 100000,
            'points_required' => 50,
            'price_after_points' => 50000,
            'stock' => 5,
        ]);

        $this->postJson('/api/v1/merchandise/order', [
            'merchandise_id' => $merch->id,
            'customer_name' => $this->participant->name,
            'customer_contact' => '0812',
            'size' => 'L',
            'quantity' => 2,
            'use_points' => true,
        ])->assertCreated();

        // REDEEM 100 points (50/unit * 2), balance 200 -> 100.
        $this->assertSame(100, $this->participant->fresh()->point_balance);

        $order = MerchandiseOrder::first();
        $this->assertSame(100, $order->points_used);
        $this->assertSame(100000.0, (float) $order->total_price); // cash after discount
        $this->assertSame(100000.0, (float) $order->cash_amount_snapshot);
        $this->assertSame(2, $order->quantity_snapshot);

        $this->assertDatabaseHas('point_transactions', [
            'participant_id' => $this->participant->id,
            'type' => PointTransaction::TYPE_REDEEM,
            'amount' => -100,
            'source_id' => $order->id,
        ]);
    }

    public function test_redemption_rejects_when_balance_insufficient(): void
    {
        $plan = MembershipPlan::factory()->create(['point_per_event_checkin' => 10]);
        $this->giveActiveMembership($this->participant, $plan);
        $event = $this->createEventAndRegister($this->participant);
        $this->checkIn($this->participant, $event);

        $merch = Merchandise::factory()->create([
            'price' => 100000,
            'points_required' => 50,
            'price_after_points' => 50000,
            'stock' => 5,
        ]);

        // Only 10 points, but 50/unit required -> reject.
        $this->postJson('/api/v1/merchandise/order', [
            'merchandise_id' => $merch->id,
            'customer_name' => $this->participant->name,
            'customer_contact' => '0812',
            'size' => 'L',
            'quantity' => 1,
            'use_points' => true,
        ])->assertStatus(422);

        $this->assertSame(10, $this->participant->fresh()->point_balance);
        $this->assertSame(0, MerchandiseOrder::count());
        $this->assertSame(0, PointTransaction::where('type', PointTransaction::TYPE_REDEEM)->count());
    }

    public function test_cancel_redemption_order_refunds_points(): void
    {
        $plan = MembershipPlan::factory()->create(['point_per_event_checkin' => 100]);
        $this->giveActiveMembership($this->participant, $plan);
        $event = $this->createEventAndRegister($this->participant);
        $this->checkIn($this->participant, $event);

        $merch = Merchandise::factory()->create([
            'price' => 100000,
            'points_required' => 50,
            'price_after_points' => 50000,
            'stock' => 5,
        ]);

        $this->postJson('/api/v1/merchandise/order', [
            'merchandise_id' => $merch->id,
            'customer_name' => $this->participant->name,
            'customer_contact' => '0812',
            'size' => 'L',
            'quantity' => 1,
            'use_points' => true,
        ])->assertCreated();

        $this->assertSame(50, $this->participant->fresh()->point_balance);

        $orderId = MerchandiseOrder::first()->id;
        $this->postJson('/api/v1/merchandise/orders/'.$orderId.'/cancel')->assertOk();

        // Points returned: 100 again.
        $this->assertSame(100, $this->participant->fresh()->point_balance);
    }

    public function test_cancel_reversal_is_idempotent(): void
    {
        $plan = MembershipPlan::factory()->create(['point_per_event_checkin' => 100]);
        $this->giveActiveMembership($this->participant, $plan);
        $event = $this->createEventAndRegister($this->participant);
        $this->checkIn($this->participant, $event);

        $merch = Merchandise::factory()->create([
            'price' => 100000,
            'points_required' => 50,
            'price_after_points' => 50000,
            'stock' => 5,
        ]);

        $this->postJson('/api/v1/merchandise/order', [
            'merchandise_id' => $merch->id,
            'customer_name' => $this->participant->name,
            'customer_contact' => '0812',
            'size' => 'L',
            'quantity' => 1,
            'use_points' => true,
        ])->assertCreated();

        $order = MerchandiseOrder::first();
        $service = app(PointService::class);

        $service->refundRedemption($this->participant, $order);
        $service->refundRedemption($this->participant, $order);

        // Only ONE reversal net of the ONE redeem: 100 - 50 + 50 = 100.
        $this->assertSame(100, $this->participant->fresh()->point_balance);
        $this->assertSame(1, PointTransaction::where('type', PointTransaction::TYPE_REVERSAL)->count());
    }

    public function test_ledger_reconciles_with_balance(): void
    {
        $plan = MembershipPlan::factory()->create(['point_per_event_checkin' => 30]);
        $this->giveActiveMembership($this->participant, $plan);
        $event = $this->createEventAndRegister($this->participant);
        $this->checkIn($this->participant, $event);

        $service = app(PointService::class);
        $this->assertSame(30, $service->balanceFromLedger($this->participant->id));
        $this->assertSame($this->participant->fresh()->point_balance, $service->balanceFromLedger($this->participant->id));
    }

    public function test_point_balance_and_history_endpoints(): void
    {
        $plan = MembershipPlan::factory()->create(['point_per_event_checkin' => 40]);
        $this->giveActiveMembership($this->participant, $plan);
        $event = $this->createEventAndRegister($this->participant);
        $this->checkIn($this->participant, $event);

        $this->getJson('/api/v1/points/balance')
            ->assertOk()
            ->assertJsonPath('data.balance', 40);

        $this->getJson('/api/v1/points/history')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }
}
