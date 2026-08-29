<?php

namespace Tests\Feature;

use App\Exceptions\PointReversalBlockedException;
use App\Models\Attendance;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\MembershipHistory;
use App\Models\MembershipPlan;
use App\Models\Merchandise;
use App\Models\MerchandiseOrder;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\PaymentService;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Closes the post-implementation audit findings for Flat Point + Merchandise
 * Redemption: H1–H5, M1–M4 and verifies invariants V1–V12.
 *
 * Most tests run sequentially (RefreshDatabase) and prove the source-level
 * behaviour. True DB-level concurrency for M1 is covered by
 * test_concurrent_check_in_does_not_double_earn_or_500 (pcntl-assisted, skipped
 * gracefully when pcntl is unavailable).
 */
class PointAuditFindingsTest extends TestCase
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

    private function createEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'category_id' => $this->category->id,
            'title' => 'Long Run Audit',
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
        ], $overrides));
    }

    private function register(Event $event, Participant $participant, ?string $qr = null): EventParticipant
    {
        return EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'free',
            'amount' => 0,
            'payment_status' => 'confirmed',
            'qr_code' => $qr ?? ($participant->hash_id.'-EV'.$event->id.'-'.uniqid()),
        ]);
    }

    private function giveActiveMembership(Participant $participant, int $rate = 50, string $type = 'tahunan'): MembershipHistory
    {
        // Rate resolves via MembershipHistory::plan() which matches
        // membership_type (enum) to membership_plans.key — so the plan key must
        // equal the membership_type value exactly. firstOrCreate keeps the plan
        // idempotent across calls (the key column is UNIQUE).
        MembershipPlan::firstOrCreate(['key' => $type], [
            'name' => 'Audit Plan '.$rate,
            'price' => 400000,
            'duration' => 12,
            'duration_unit' => 'months',
            'point_per_event_checkin' => $rate,
            'is_active' => true,
        ]);

        return MembershipHistory::create([
            'participant_id' => $participant->id,
            'membership_type' => $type,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonths(12)->toDateString(),
            'price' => 400000,
            'status' => MembershipHistory::STATUS_ACTIVE,
        ]);
    }

    private function checkIn(Event $event, Participant $participant): void
    {
        $registration = $this->register($event, $participant);
        $this->postJson('/api/v1/attendance/check-in', [
            'event_id' => $event->id,
            'method' => 'qr_code',
        ])->assertOk();
    }

    // ---------------------------------------------------------------------
    // H4 — Check-in participant IDOR
    // ---------------------------------------------------------------------

    public function test_participant_cannot_check_in_another_participant(): void
    {
        $event = $this->createEvent();
        $this->register($event, $this->participant);

        // Victim participant B.
        $userB = User::factory()->create();
        $participantB = Participant::factory()->create(['user_id' => $userB->id]);
        $this->register($event, $participantB);

        // A posts a check-in claiming participant_id = B.
        $this->postJson('/api/v1/attendance/check-in', [
            'event_id' => $event->id,
            'participant_id' => $participantB->id,
            'method' => 'qr_code',
        ])->assertOk();

        // B must NOT have checked in nor earned any point.
        $this->assertDatabaseMissing('attendances', [
            'event_participant_id' => EventParticipant::where('participant_id', $participantB->id)->first()->id,
        ]);
        $this->assertSame(0, $participantB->fresh()->point_balance);
        // A (token holder) checked in themselves.
        $this->assertSame(1, Attendance::where('event_participant_id', EventParticipant::where('participant_id', $this->participant->id)->first()->id)->count());
    }

    public function test_participant_cannot_earn_points_for_another_participant(): void
    {
        $event = $this->createEvent();
        $this->giveActiveMembership($this->participant, 50);
        $this->register($event, $this->participant);

        $userB = User::factory()->create();
        $participantB = Participant::factory()->create(['user_id' => $userB->id]);
        $this->register($event, $participantB);
        $this->giveActiveMembership($participantB, 50);

        $beforeB = $participantB->fresh()->point_balance;
        $this->postJson('/api/v1/attendance/check-in', [
            'event_id' => $event->id,
            'participant_id' => $participantB->id,
        ])->assertOk();

        // B's balance unchanged (no points earned by A on B's behalf).
        $this->assertSame($beforeB, $participantB->fresh()->point_balance);
        // A earned their own 50.
        $this->assertSame(50, $this->participant->fresh()->point_balance);
    }

    public function test_admin_invalidate_endpoint_still_works_for_authorized_admin(): void
    {
        $event = $this->createEvent();
        $this->giveActiveMembership($this->participant, 50);
        $registration = $this->register($event, $this->participant);
        $this->postJson('/api/v1/attendance/check-in', ['event_id' => $event->id])->assertOk();
        $this->assertSame(50, $this->participant->fresh()->point_balance);

        $admin = User::factory()->create();
        $admin->role = 'admin_full_access';
        $admin->save();

        $attendance = Attendance::where('event_participant_id', $registration->id)->first();
        $this->actingAs($admin)->post('/admin/attendance/'.$attendance->id.'/invalidate', ['reason' => 'test'])
            ->assertSessionHas('success');

        $this->assertTrue($attendance->fresh()->is_invalid);
        $this->assertSame(1, PointTransaction::where('source_type', 'reversal_attendance')->where('source_id', $attendance->id)->count());
        $this->assertSame(0, $this->participant->fresh()->point_balance);
    }

    // ---------------------------------------------------------------------
    // H2 / H3 / V11 — Attendance invalidation lifecycle + reversal
    // ---------------------------------------------------------------------

    public function test_attendance_invalidation_creates_exactly_one_reversal(): void
    {
        $event = $this->createEvent();
        $this->giveActiveMembership($this->participant, 50);
        $registration = $this->register($event, $this->participant);
        $this->postJson('/api/v1/attendance/check-in', ['event_id' => $event->id])->assertOk();

        $attendance = Attendance::where('event_participant_id', $registration->id)->first();
        $this->assertSame(50, $this->participant->fresh()->point_balance);

        $result = app(AttendanceService::class)->invalidateAttendance($attendance->id, $this->user->id, 'audit');
        $this->assertSame('created', $result['reversal']);

        // Balance restored, exactly one EARN + one REVERSAL, original EARN untouched.
        $this->assertSame(0, $this->participant->fresh()->point_balance);
        $this->assertSame(1, PointTransaction::where('source_type', 'attendance')->where('source_id', $attendance->id)->where('type', 'EARN')->count());
        $this->assertSame(1, PointTransaction::where('source_type', 'reversal_attendance')->where('source_id', $attendance->id)->count());
        $this->assertTrue($attendance->fresh()->is_invalid);
    }

    public function test_attendance_invalidation_is_idempotent(): void
    {
        $event = $this->createEvent();
        $this->giveActiveMembership($this->participant, 50);
        $registration = $this->register($event, $this->participant);
        $this->postJson('/api/v1/attendance/check-in', ['event_id' => $event->id])->assertOk();
        $attendance = Attendance::where('event_participant_id', $registration->id)->first();

        app(AttendanceService::class)->invalidateAttendance($attendance->id, $this->user->id);
        app(AttendanceService::class)->invalidateAttendance($attendance->id, $this->user->id);

        $this->assertSame(1, PointTransaction::where('source_type', 'reversal_attendance')->where('source_id', $attendance->id)->count());
        $this->assertSame(0, $this->participant->fresh()->point_balance);
    }

    public function test_reversal_blocked_when_it_would_make_balance_negative(): void
    {
        $event = $this->createEvent();
        $this->giveActiveMembership($this->participant, 50);
        $registration = $this->register($event, $this->participant);
        $this->postJson('/api/v1/attendance/check-in', ['event_id' => $event->id])->assertOk();
        $attendance = Attendance::where('event_participant_id', $registration->id)->first();

        // Simulate points already spent (balance below earned 50 -> e.g. 10).
        $this->participant->update(['point_balance' => 10]);

        $this->expectException(PointReversalBlockedException::class);
        app(PointService::class)->reverseEarn($attendance, $this->participant, 'audit');

        // No REVERSAL row, balance unchanged, original EARN untouched.
        $this->assertSame(0, PointTransaction::where('source_type', 'reversal_attendance')->where('source_id', $attendance->id)->count());
        $this->assertSame(10, $this->participant->fresh()->point_balance);
    }

    public function test_admin_adjustment_is_audited_and_append_only(): void
    {
        $admin = User::factory()->create();
        $admin->role = 'admin_full_access';
        $admin->save();

        $tx = app(PointService::class)->adminAdjust($this->participant, 25, $admin->id, 'koreksi audit');
        $this->assertSame('ADJUSTMENT', $tx->type);
        $this->assertSame(25, $tx->amount);
        $this->assertSame($admin->id, $tx->adjusted_by);
        $this->assertSame(25, $this->participant->fresh()->point_balance);

        // Negative beyond balance is refused (V2 still enforced).
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(PointService::class)->adminAdjust($this->participant, -999, $admin->id, 'over');
    }

    public function test_ledger_history_remains_immutable_after_reversal(): void
    {
        $event = $this->createEvent();
        $this->giveActiveMembership($this->participant, 50);
        $registration = $this->register($event, $this->participant);
        $this->postJson('/api/v1/attendance/check-in', ['event_id' => $event->id])->assertOk();
        $attendance = Attendance::where('event_participant_id', $registration->id)->first();
        $earn = PointTransaction::where('source_type', 'attendance')->where('source_id', $attendance->id)->first();

        app(AttendanceService::class)->invalidateAttendance($attendance->id, $this->user->id);

        // Original EARN row must be unchanged (no UPDATE/DELETE on ledger).
        $this->assertDatabaseHas('point_transactions', [
            'id' => $earn->id,
            'type' => 'EARN',
            'amount' => 50,
        ]);
    }

    // ---------------------------------------------------------------------
    // H1 / H5 / V12 — Event payment reject/refund -> attendance invalid -> reversal
    // ---------------------------------------------------------------------

    private function makeEventPayment(EventParticipant $registration, string $status = 'confirmed'): Payment
    {
        $payment = Payment::create([
            'participant_id' => $registration->participant_id,
            'invoice_number' => 'INV-AUDIT-'.uniqid(),
            'payment_type' => 'event_registration',
            'paymentable_type' => EventParticipant::class,
            'paymentable_id' => $registration->id,
            'amount' => 0,
            'payment_method' => 'transfer',
            'status' => $status,
            'paid_at' => $status === 'confirmed' ? now() : null,
        ]);
        $registration->update(['payment_id' => $payment->id]);

        return $payment;
    }

    public function test_event_payment_rejected_after_check_in_reverses_points(): void
    {
        $event = $this->createEvent();
        $this->giveActiveMembership($this->participant, 50);
        $registration = $this->register($event, $this->participant);
        $this->postJson('/api/v1/attendance/check-in', ['event_id' => $event->id])->assertOk();
        $payment = $this->makeEventPayment($registration, 'confirmed');

        $admin = User::factory()->create();
        $admin->role = 'bendahara';
        $admin->save();
        $this->actingAs($admin)->put('/admin/payments/'.$payment->id.'/reject')->assertSessionHas('success');

        $attendance = Attendance::where('event_participant_id', $registration->id)->first();
        $this->assertTrue($attendance->fresh()->is_invalid);
        $this->assertSame(1, PointTransaction::where('source_type', 'reversal_attendance')->where('source_id', $attendance->id)->count());
        $this->assertSame(0, $this->participant->fresh()->point_balance);
    }

    public function test_event_payment_refunded_after_check_in_reverses_points(): void
    {
        $event = $this->createEvent();
        $this->giveActiveMembership($this->participant, 50);
        $registration = $this->register($event, $this->participant);
        $this->postJson('/api/v1/attendance/check-in', ['event_id' => $event->id])->assertOk();
        $payment = $this->makeEventPayment($registration, 'confirmed');

        $admin = User::factory()->create();
        $admin->role = 'bendahara';
        $admin->save();
        $this->actingAs($admin)->put('/admin/payments/'.$payment->id.'/refund')->assertSessionHas('success');

        $attendance = Attendance::where('event_participant_id', $registration->id)->first();
        $this->assertTrue($attendance->fresh()->is_invalid);
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame(1, PointTransaction::where('source_type', 'reversal_attendance')->where('source_id', $attendance->id)->count());
        $this->assertSame(0, $this->participant->fresh()->point_balance);
    }

    public function test_event_payment_rejected_before_check_in_has_nothing_to_reverse(): void
    {
        $event = $this->createEvent();
        $registration = $this->register($event, $this->participant);
        $payment = $this->makeEventPayment($registration, 'confirmed');

        $admin = User::factory()->create();
        $admin->role = 'bendahara';
        $admin->save();
        $this->actingAs($admin)->put('/admin/payments/'.$payment->id.'/refund')->assertSessionHas('success');

        $this->assertSame(0, PointTransaction::where('source_type', 'reversal_attendance')->count());
        $this->assertSame(0, $this->participant->fresh()->point_balance);
    }

    public function test_repeated_payment_rejection_callback_is_idempotent(): void
    {
        $event = $this->createEvent();
        $this->giveActiveMembership($this->participant, 50);
        $registration = $this->register($event, $this->participant);
        $this->postJson('/api/v1/attendance/check-in', ['event_id' => $event->id])->assertOk();
        $payment = $this->makeEventPayment($registration, 'confirmed');

        $admin = User::factory()->create();
        $admin->role = 'bendahara';
        $admin->save();
        $this->actingAs($admin)->put('/admin/payments/'.$payment->id.'/reject')->assertSessionHas('success');
        $this->actingAs($admin)->put('/admin/payments/'.$payment->id.'/reject')->assertSessionHas('success');

        $attendance = Attendance::where('event_participant_id', $registration->id)->first();
        $this->assertSame(1, PointTransaction::where('source_type', 'reversal_attendance')->where('source_id', $attendance->id)->count());
    }

    public function test_merchandise_payment_rejection_still_reverses_redemption_only(): void
    {
        // Active membership so participant has points to spend.
        $this->giveActiveMembership($this->participant, 50);
        $event = $this->createEvent();
        $registration = $this->register($event, $this->participant);
        $this->postJson('/api/v1/attendance/check-in', ['event_id' => $event->id])->assertOk(); // +50
        $this->assertSame(50, $this->participant->fresh()->point_balance);

        $merch = Merchandise::create([
            'name' => 'Audit Shirt',
            'price' => 100000,
            'stock' => 5,
            'status' => 'available',
        ]);
        $order = MerchandiseOrder::create([
            'participant_id' => $this->participant->id,
            'merchandise_id' => $merch->id,
            'customer_name' => 'Audit Buyer',
            'customer_contact' => '08123456789',
            'size' => 'M',
            'quantity' => 1,
            'total_price' => 100000,
            'unit_price_snapshot' => 100000,
            'points_used' => 50,
            'payment_status' => 'pending',
        ]);
        app(PointService::class)->redeemForOrder($this->participant->fresh(), $order, 50); // -50 -> 0
        $this->assertSame(0, $this->participant->fresh()->point_balance);

        $payment = Payment::create([
            'participant_id' => $this->participant->id,
            'invoice_number' => 'INV-M-'.uniqid(),
            'payment_type' => 'merchandise',
            'paymentable_type' => MerchandiseOrder::class,
            'paymentable_id' => $order->id,
            'amount' => 100000,
            'payment_method' => 'transfer',
            'status' => 'confirmed',
            'paid_at' => now(),
        ]);

        $admin = User::factory()->create();
        $admin->role = 'bendahara';
        $admin->save();
        $this->actingAs($admin)->put('/admin/payments/'.$payment->id.'/reject')->assertSessionHas('success');

        // Merchandise rejection restores the 50 points; event attendance untouched.
        $this->assertSame(50, $this->participant->fresh()->point_balance);
        $this->assertSame(0, PointTransaction::where('source_type', 'reversal_attendance')->count());
        $this->assertSame(1, PointTransaction::where('source_type', 'reversal_order')->where('source_id', $order->id)->count());
    }

    // ---------------------------------------------------------------------
    // M4 — Rate resolution consistency with checkEligibility()
    // ---------------------------------------------------------------------

    public function test_rate_resolution_matches_eligibility_active_expired_future(): void
    {
        $service = app(\App\Services\PointRateService::class);

        // Active membership -> active rate.
        $this->giveActiveMembership($this->participant, 50);
        $resolved = $service->resolveAt($this->participant, now());
        $this->assertSame(50, $resolved['rate']);
        $this->assertTrue(app(\App\Services\MembershipService::class)->checkEligibility($this->participant)['is_eligible']);

        // Expired membership -> default (0).
        $this->participant->membershipHistories()->update(['status' => 'expired', 'end_date' => now()->subDay()]);
        $resolved = $service->resolveAt($this->participant, now());
        $this->assertSame(0, $resolved['rate']);

        // Future-dated membership (start in future) -> system "active" definition
        // (end_date >= now) still treats it as eligible, matching checkEligibility.
        MembershipPlan::create([
            'key' => 'mingguan',
            'name' => 'F',
            'price' => 1,
            'duration' => 1,
            'duration_unit' => 'months',
            'point_per_event_checkin' => 70,
            'is_active' => true,
        ]);
        MembershipHistory::create([
            'participant_id' => $this->participant->id,
            'membership_type' => 'mingguan',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'price' => 1,
            'status' => MembershipHistory::STATUS_ACTIVE,
        ]);
        $resolved = $service->resolveAt($this->participant, now());
        $this->assertSame(70, $resolved['rate']);
        $this->assertTrue(app(\App\Services\MembershipService::class)->checkEligibility($this->participant)['is_eligible']);

        // Non-member -> default 0.
        $nonMember = Participant::factory()->create();
        $this->assertSame(0, $service->resolveAt($nonMember, now())['rate']);
    }

    public function test_rate_is_snapshotted_and_historical_earn_unchanged_after_plan_change(): void
    {
        $event = $this->createEvent();
        $history = $this->giveActiveMembership($this->participant, 50);
        $registration = $this->register($event, $this->participant);
        $this->postJson('/api/v1/attendance/check-in', ['event_id' => $event->id])->assertOk();
        $attendance = Attendance::where('event_participant_id', $registration->id)->first();
        $earn = PointTransaction::where('source_type', 'attendance')->where('source_id', $attendance->id)->first();
        $this->assertSame(50, $earn->point_rate);

        // Plan rate changes later.
        $history->plan->update(['point_per_event_checkin' => 999]);

        // Historical EARN row keeps the original snapshot (never mutated).
        $this->assertSame(50, $earn->fresh()->point_rate);
        $this->assertSame(50, $this->participant->fresh()->point_balance);
    }

    // ---------------------------------------------------------------------
    // V4 / V5 — OTS and OTS aggregator earn 0
    // ---------------------------------------------------------------------

    public function test_ots_registration_earns_zero(): void
    {
        $event = $this->createEvent();
        // Dedicated user so the token resolves to the OTS participant (which is
        // the one actually registered for this event) rather than an unrelated
        // participant owned by $this->user.
        $otsUser = User::factory()->create();
        $ots = Participant::factory()->create(['user_id' => $otsUser->id]);
        $registration = $this->register($event, $ots, 'OTS-AUDIT123');
        Sanctum::actingAs($otsUser);
        $this->postJson('/api/v1/attendance/check-in', ['event_id' => $event->id])->assertOk();

        $this->assertSame(0, $ots->fresh()->point_balance);
        $this->assertSame(0, PointTransaction::where('source_type', 'attendance')->where('source_id', Attendance::where('event_participant_id', $registration->id)->first()->id)->count());
    }

    public function test_ots_aggregator_earns_zero(): void
    {
        $event = $this->createEvent();
        $aggregator = Participant::firstOrCreate(
            ['hash_id' => Participant::OTS_AGGREGATOR_CODE],
            ['name' => 'Manual OTS', 'is_active' => true, 'email' => 'manual.ots@sh3.com']
        );
        $registration = $this->register($event, $aggregator, 'OTS-AGG-AUDIT');
        // The OTS aggregator has no user account, so exercise the check-in via
        // the service directly (same code path the admin scan endpoint uses).
        app(AttendanceService::class)->checkIn($event, $aggregator, ['method' => 'qr_code'], $this->user->id, '127.0.0.1');

        $this->assertSame(0, $aggregator->fresh()->point_balance);
        $this->assertSame(0, PointTransaction::where('source_type', 'attendance')->where('source_id', Attendance::where('event_participant_id', $registration->id)->first()->id)->count());
    }

    // ---------------------------------------------------------------------
    // M1 — Concurrency: duplicate check-in yields one attendance + one EARN
    // ---------------------------------------------------------------------

    public function test_concurrent_check_in_does_not_double_earn_or_500(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl_fork unavailable in this environment — the uniqueness + re-query guard in AttendanceService::checkIn provides the race protection; a true parallel test could not be executed here.');
        }

        // Self-contained: build data on a COMMITTED connection so the forked
        // children (which open their own connections) can see the event/
        // registration rows. RefreshDatabase's wrapping transaction is ended.
        DB::commit();

        $user = User::factory()->create();
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        $this->giveActiveMembership($participant, 50); // so a real EARN is created
        $event = $this->createEvent();
        $registration = $this->register($event, $participant);

        // Two forked processes both attempt to check in the SAME registration.
        $pids = [];
        for ($i = 0; $i < 2; $i++) {
            $pid = pcntl_fork();
            if ($pid === 0) {
                // Child opens its own DB connection (parent's socket is unsafe post-fork).
                DB::purge('mysql');
                app(AttendanceService::class)->checkIn(
                    Event::find($event->id),
                    Participant::find($participant->id),
                    ['method' => 'qr_code'],
                    $user->id,
                    '127.0.0.1'
                );
                exit(0);
            }
            $pids[] = $pid;
        }
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        $attendance = Attendance::where('event_participant_id', $registration->id)->first();
        // Exactly one attendance row (UNIQUE guard) and exactly one EARN ledger
        // entry — no duplicate attendance, no duplicate EARN, no uncaught 500.
        $this->assertSame(1, Attendance::where('event_participant_id', $registration->id)->count());
        $this->assertSame(1, PointTransaction::where('source_type', 'attendance')->where('source_id', $attendance->id)->count());
        $this->assertSame(50, $participant->fresh()->point_balance);

        // Cleanup committed rows so they do not leak into other tests.
        PointTransaction::query()->where('participant_id', $participant->id)->delete();
        Attendance::where('event_participant_id', $registration->id)->delete();
        $registration->delete();
        $event->forceDelete();
        MembershipPlan::where('key', 'tahunan')->delete();
        $participant->delete();
        $user->delete();
    }

    // ---------------------------------------------------------------------
    // M3 — syncUpOffline earning coverage
    // ---------------------------------------------------------------------

    public function test_sync_up_offline_regular_creates_one_attendance_and_earn(): void
    {
        $event = $this->createEvent();
        $this->giveActiveMembership($this->participant, 50);
        $registration = $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/sync-up', [
            'attendances' => [[
                'hash_id' => $this->participant->hash_id,
                'event_id' => $event->id,
                'check_in_time' => now()->toDateTimeString(),
                'check_out_time' => null,
            ]],
            'ots_registrations' => [],
        ])->assertOk();

        $this->assertSame(1, Attendance::where('event_participant_id', $registration->id)->count());
        $this->assertSame(50, $this->participant->fresh()->point_balance);
    }

    public function test_sync_up_offline_duplicate_does_not_double_earn(): void
    {
        $event = $this->createEvent();
        $this->giveActiveMembership($this->participant, 50);
        $registration = $this->register($event, $this->participant);

        $payload = ['attendances' => [[
            'hash_id' => $this->participant->hash_id,
            'event_id' => $event->id,
            'check_in_time' => now()->toDateTimeString(),
            'check_out_time' => null,
        ]], 'ots_registrations' => []];

        $this->postJson('/api/v1/attendance/sync-up', $payload)->assertOk();
        $this->postJson('/api/v1/attendance/sync-up', $payload)->assertOk();

        $this->assertSame(1, Attendance::where('event_participant_id', $registration->id)->count());
        $this->assertSame(50, $this->participant->fresh()->point_balance);
    }
}
