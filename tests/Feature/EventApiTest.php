<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\MembershipHistory;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Long Run',
            'slug' => 'long-run',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    private function createEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'category_id' => $this->category->id,
            'title' => 'Long Run Test',
            'description' => 'Deskripsi event',
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

    public function test_list_events_only_shows_public(): void
    {
        $this->createEvent(['status' => 'publish']);
        $this->createEvent(['status' => 'draft']);
        $this->createEvent(['status' => 'ongoing']);
        $this->createEvent(['status' => 'completed']);

        $response = $this->getJson('/api/v1/events');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'category']]])
            ->assertJsonMissing(['status' => 'draft']);
    }

    public function test_show_event_returns_detail(): void
    {
        $event = $this->createEvent();

        $this->getJson('/api/v1/events/'.$event->id)
            ->assertOk()
            ->assertJsonPath('data.id', $event->id)
            ->assertJsonPath('data.title', $event->title)
            ->assertJsonStructure(['data' => ['schedules', 'remaining_quota']]);
    }

    public function test_show_nonexistent_event_returns_404(): void
    {
        $this->getJson('/api/v1/events/999')->assertNotFound();
    }

    public function test_upcoming_returns_publish_and_ongoing_future_events(): void
    {
        $this->createEvent(['status' => 'publish', 'start_date' => now()->addDays(3)]);
        $this->createEvent(['status' => 'ongoing', 'start_date' => now()->addDays(1)]);
        $this->createEvent(['status' => 'publish', 'start_date' => now()->subDays(3)]);

        $response = $this->getJson('/api/v1/events/upcoming');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_store_event_requires_authentication(): void
    {
        $this->postJson('/api/v1/events', [])->assertUnauthorized();
    }

    public function test_store_event_rejects_non_admin_role(): void
    {
        $participantUser = User::factory()->create(['role' => 'participant']);
        Sanctum::actingAs($participantUser);

        $this->postJson('/api/v1/events', $this->validEventPayload())
            ->assertForbidden();
    }

    public function test_admin_can_create_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/events', $this->validEventPayload())
            ->assertCreated()
            ->assertJsonPath('message', 'Event berhasil dibuat')
            ->assertJsonPath('data.title', 'New Event');

        $this->assertDatabaseHas('events', ['title' => 'New Event', 'status' => 'publish']);
    }

    public function test_admin_can_update_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        Sanctum::actingAs($admin);

        $event = $this->createEvent();

        $this->putJson('/api/v1/events/'.$event->id, $this->validEventPayload(['title' => 'Updated Title']))
            ->assertOk()
            ->assertJsonPath('message', 'Event berhasil diupdate')
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Updated Title']);
    }

    public function test_admin_can_delete_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        Sanctum::actingAs($admin);

        $event = $this->createEvent();

        $this->deleteJson('/api/v1/events/'.$event->id)
            ->assertOk()
            ->assertJsonPath('message', 'Event berhasil dihapus');

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_organizer_can_create_event(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        Sanctum::actingAs($organizer);

        $this->postJson('/api/v1/events', $this->validEventPayload())
            ->assertCreated();
    }

    public function test_participants_is_public(): void
    {
        $event = $this->createEvent();
        $participant = Participant::factory()->create();
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'free',
            'amount' => 0,
            'payment_status' => 'confirmed',
            'qr_code' => $participant->hash_id,
        ]);

        $this->getJson('/api/v1/events/'.$event->id.'/participants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.participant.id', $participant->id);

        $participantUser = User::factory()->create(['role' => 'participant']);
        Sanctum::actingAs($participantUser);

        $this->getJson('/api/v1/events/'.$event->id.'/participants')->assertOk();
    }

    public function test_admin_can_view_participants(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        Sanctum::actingAs($admin);

        $event = $this->createEvent();
        $participant = Participant::factory()->create();
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'free',
            'amount' => 0,
            'payment_status' => 'confirmed',
            'qr_code' => $participant->hash_id,
        ]);

        $this->getJson('/api/v1/events/'.$event->id.'/participants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.participant.id', $participant->id);
    }

    public function test_admin_can_view_qr_codes(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        Sanctum::actingAs($admin);

        $event = $this->createEvent();
        $participant = Participant::factory()->create();
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'free',
            'amount' => 0,
            'payment_status' => 'confirmed',
            'qr_code' => $participant->hash_id,
        ]);

        $this->getJson('/api/v1/events/'.$event->id.'/qr')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.participant_name', $participant->name)
            ->assertJsonPath('data.0.qr_code', $participant->hash_id);
    }

    public function test_register_free_event_success(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 0, 'quota' => 5]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertOk()
            ->assertJsonPath('message', 'Pendaftaran berhasil');

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'free',
            'payment_status' => 'confirmed',
        ]);
    }

    public function test_register_paid_event_creates_pending_registration(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertOk();

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'paid',
            'payment_status' => 'pending',
        ]);
    }

    public function test_register_when_quota_full_returns_422(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['quota' => 1]);
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => Participant::factory()->create()->id,
            'registration_type' => 'free',
            'amount' => 0,
            'payment_status' => 'confirmed',
        ]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertUnprocessable()
            ->assertJsonPath('errors.event.0', 'Kuota event sudah penuh.');
    }

    public function test_register_duplicate_returns_422(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['quota' => 5]);
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'free',
            'amount' => 0,
            'payment_status' => 'confirmed',
        ]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertUnprocessable()
            ->assertJsonPath('errors.event.0', 'Anda sudah terdaftar di event ini.');
    }

    public function test_register_requires_authentication(): void
    {
        $event = $this->createEvent();

        $this->postJson('/api/v1/events/'.$event->id.'/register')->assertUnauthorized();
    }

    public function test_non_member_paid_registration_returns_pending(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'pending');

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'paid',
            'payment_status' => 'pending',
        ]);
    }

    public function test_admin_rejection_marks_registration_rejected_not_pending(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')->assertOk();

        $registration = EventParticipant::where('event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->firstOrFail();

        $payment = Payment::create([
            'participant_id' => $participant->id,
            'invoice_number' => 'INV/'.now()->format('Ymd').'/REJ001',
            'payment_type' => 'event_registration',
            'paymentable_type' => EventParticipant::class,
            'paymentable_id' => $registration->id,
            'amount' => 150000,
            'payment_method' => 'transfer',
            'payment_proof' => null,
            'status' => 'pending',
        ]);

        $bendahara = User::factory()->create(['role' => 'bendahara']);

        $this->app->make(PaymentService::class)->rejectPayment($payment, $bendahara->id);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'rejected',
        ]);

        $this->assertSame('rejected', $registration->fresh()->payment_status);

        $this->getJson('/api/v1/my-events')
            ->assertOk()
            ->assertJsonPath('data.0.order.status', 'rejected');
    }

    public function test_active_member_free_registration_auto_confirmed_and_paid(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        MembershipHistory::factory()->create([
            'participant_id' => $participant->id,
            'membership_type' => 'tahunan',
            'status' => 'active',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5, 'is_free_for_members' => true]);

        $response = $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertOk();

        $registration = EventParticipant::where('event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->firstOrFail();

        $this->assertSame('membership', $registration->registration_type);
        $this->assertSame('confirmed', $registration->payment_status);
        $this->assertTrue((bool) $registration->is_membership_free);
        $this->assertEquals(0, (float) $registration->amount);
        $this->assertNotNull($registration->qr_code);
        $this->assertNull($registration->payment_id);
        $this->assertDatabaseMissing('payments', ['participant_id' => $participant->id]);

        $response->assertJsonPath('data.payment_status', 'confirmed')
            ->assertJsonPath('data.qr_code', $registration->qr_code)
            ->assertJsonPath('data.ticket_code', $registration->qr_code);

        $this->getJson('/api/v1/my-events')
            ->assertOk()
            ->assertJsonPath('data.0.order.status', 'paid')
            ->assertJsonPath('data.0.order.ticket_code', $registration->qr_code)
            ->assertJsonPath('data.0.order.attendance.qr_code', $registration->qr_code);
    }

    public function test_expired_membership_does_not_get_free_auto_registration(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        MembershipHistory::factory()->expired()->create([
            'participant_id' => $participant->id,
        ]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5, 'is_free_for_members' => true]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'pending');

        $registration = EventParticipant::where('event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->firstOrFail();

        $this->assertSame('paid', $registration->registration_type);
        $this->assertSame('pending', $registration->payment_status);
        $this->assertFalse((bool) $registration->is_membership_free);
        $this->assertGreaterThan(0, (float) $registration->amount);
    }

    public function test_existing_paid_registration_keeps_paid_status_and_qr(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5]);
        $qr = $participant->hash_id;
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'paid',
            'amount' => 150000,
            'payment_status' => 'confirmed',
            'qr_code' => $qr,
        ]);

        $this->getJson('/api/v1/my-events')
            ->assertOk()
            ->assertJsonPath('data.0.order.status', 'paid')
            ->assertJsonPath('data.0.order.ticket_code', $qr)
            ->assertJsonPath('data.0.order.attendance.qr_code', $qr);
    }

    public function test_pending_registration_blocks_duplicate_registration(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5]);
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'paid',
            'amount' => 150000,
            'payment_status' => 'pending',
            'qr_code' => $participant->hash_id,
        ]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertUnprocessable()
            ->assertJsonPath('errors.event.0', 'Anda masih memiliki pendaftaran yang sedang diproses.');
    }

    public function test_paid_registration_blocks_duplicate_and_keeps_qr(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5]);
        $qr = $participant->hash_id;
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'paid',
            'amount' => 150000,
            'payment_status' => 'confirmed',
            'qr_code' => $qr,
        ]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertUnprocessable()
            ->assertJsonPath('errors.event.0', 'Anda sudah terdaftar di event ini.');

        $this->getJson('/api/v1/my-events')
            ->assertOk()
            ->assertJsonPath('data.0.order.status', 'paid')
            ->assertJsonPath('data.0.order.ticket_code', $qr);
    }

    public function test_rejected_registration_allows_registering_again(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create([
            'user_id' => $user->id,
            'total_events_participated' => 1,
        ]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5]);
        $oldQr = $participant->hash_id;
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'paid',
            'amount' => 150000,
            'payment_status' => 'rejected',
            'qr_code' => $oldQr,
        ]);

        $response = $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'pending');

        $registration = EventParticipant::where('event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->firstOrFail();

        $this->assertSame('pending', $registration->payment_status);
        $this->assertNotNull($registration->qr_code);
        $this->assertSame($participant->hash_id, $registration->qr_code);
        $this->assertSame(1, EventParticipant::where('event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->count());
        $this->assertSame(1, $participant->fresh()->total_events_participated);
        $this->assertNotNull($registration->payment_id);

        $response->assertJsonPath('data.qr_code', $registration->qr_code);
    }

    public function test_refunded_registration_allows_registering_again(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5]);
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'paid',
            'amount' => 150000,
            'payment_status' => 'refunded',
            'qr_code' => $participant->hash_id,
        ]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'pending');

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'payment_status' => 'pending',
        ]);
    }

    public function test_rejected_registration_then_active_member_registers_free_and_paid(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        MembershipHistory::factory()->create([
            'participant_id' => $participant->id,
            'membership_type' => 'tahunan',
            'status' => 'active',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5, 'is_free_for_members' => true]);
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'paid',
            'amount' => 150000,
            'payment_status' => 'rejected',
            'qr_code' => $participant->hash_id,
        ]);

        $response = $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertOk();

        $registration = EventParticipant::where('event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->firstOrFail();

        $this->assertSame('membership', $registration->registration_type);
        $this->assertSame('confirmed', $registration->payment_status);
        $this->assertTrue((bool) $registration->is_membership_free);
        $this->assertEquals(0, (float) $registration->amount);
        $this->assertNotNull($registration->qr_code);
        $this->assertNull($registration->payment_id);

        $response->assertJsonPath('data.payment_status', 'confirmed')
            ->assertJsonPath('data.qr_code', $registration->qr_code);

        $this->getJson('/api/v1/my-events')
            ->assertOk()
            ->assertJsonPath('data.0.order.status', 'paid')
            ->assertJsonPath('data.0.order.ticket_code', $registration->qr_code)
            ->assertJsonPath('data.0.order.attendance.qr_code', $registration->qr_code);
    }

    public function test_refunded_registration_then_active_member_registers_free_and_paid(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        MembershipHistory::factory()->create([
            'participant_id' => $participant->id,
            'membership_type' => 'tahunan',
            'status' => 'active',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5, 'is_free_for_members' => true]);
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'paid',
            'amount' => 150000,
            'payment_status' => 'refunded',
            'qr_code' => $participant->hash_id,
        ]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'confirmed')
            ->assertJsonPath('data.qr_code', fn ($qr) => is_string($qr) && $qr !== '');

        $registration = EventParticipant::where('event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->firstOrFail();

        $this->assertSame('confirmed', $registration->payment_status);
        $this->assertTrue((bool) $registration->is_membership_free);
        $this->assertEquals(0, (float) $registration->amount);
    }

    public function test_rejected_registration_then_non_member_registers_pending(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 150000, 'quota' => 5, 'is_free_for_members' => true]);
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'paid',
            'amount' => 150000,
            'payment_status' => 'rejected',
            'qr_code' => $participant->hash_id,
        ]);

        $this->postJson('/api/v1/events/'.$event->id.'/register')
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'pending');

        $registration = EventParticipant::where('event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->firstOrFail();

        $this->assertSame('paid', $registration->registration_type);
        $this->assertFalse((bool) $registration->is_membership_free);
        $this->assertGreaterThan(0, (float) $registration->amount);
        $this->assertNotNull($registration->payment_id);
    }

    public function test_upcoming_remaining_quota_matches_confirmed_participants(): void
    {
        $event = $this->createEvent(['quota' => 10, 'status' => 'publish', 'start_date' => now()->addDays(3)]);

        for ($i = 0; $i < 3; $i++) {
            EventParticipant::create([
                'event_id' => $event->id,
                'participant_id' => Participant::factory()->create()->id,
                'registration_type' => 'free',
                'amount' => 0,
                'payment_status' => 'confirmed',
                'qr_code' => 'q' . $event->id . '_' . $i,
            ]);
        }

        $this->getJson('/api/v1/events/upcoming')
            ->assertOk()
            ->assertJsonPath('data.0.remaining_quota', 7);
    }

    public function test_upcoming_does_not_run_n_plus_one_count_queries(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $event = $this->createEvent(['quota' => 5, 'status' => 'publish', 'start_date' => now()->addDays($i + 1)]);
            for ($j = 0; $j < 2; $j++) {
                EventParticipant::create([
                    'event_id' => $event->id,
                    'participant_id' => Participant::factory()->create()->id,
                    'registration_type' => 'free',
                    'amount' => 0,
                    'payment_status' => 'confirmed',
                    'qr_code' => 'q' . $event->id . '_' . $j,
                ]);
            }
        }

        DB::enableQueryLog();
        $response = $this->getJson('/api/v1/events/upcoming')->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $standaloneCounts = collect($queries)->filter(
            fn($q) => preg_match('#^select count\(\*\) from `?event_participants`?#i', $q['query'])
        );
        $this->assertCount(0, $standaloneCounts, 'N+1 COUNT per event must be eliminated');

        $data = $response->json('data');
        $this->assertCount(3, $data);
        foreach ($data as $item) {
            $this->assertEquals(3, $item['remaining_quota']);
        }
    }

    public function test_upcoming_is_cached_and_second_request_runs_fewer_queries(): void
    {
        $this->createEvent(['status' => 'publish', 'start_date' => now()->addDays(3)]);

        $log = [];
        DB::listen(function ($query) use (&$log) {
            $log[] = $query->sql;
        });

        $this->getJson('/api/v1/events/upcoming');
        $missLog = $log;
        $log = [];

        $this->getJson('/api/v1/events/upcoming');
        $hitLog = $log;

        $this->assertLessThan(count($missLog), count($hitLog), 'cache hit should run fewer queries');
        $this->assertSame(0, count($hitLog), 'cache hit must run zero DB queries');

        $touchesEvents = collect($hitLog)->filter(
            fn($sql) => str_contains($sql, '`events`')
                || preg_match('#from `?event_participants`?#i', $sql)
        );
        $this->assertCount(0, $touchesEvents, 'cache hit must not query events or participants');
    }

    public function test_upcoming_cache_is_populated_on_miss(): void
    {
        $this->createEvent(['status' => 'publish', 'start_date' => now()->addDays(3)]);

        $this->assertFalse(Cache::has('api:events:upcoming'));
        $this->getJson('/api/v1/events/upcoming')->assertOk();
        $this->assertTrue(Cache::has('api:events:upcoming'));
    }

    public function test_event_delete_invalidates_upcoming_cache(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        $event = $this->createEvent(['status' => 'publish', 'start_date' => now()->addDays(3)]);

        $this->getJson('/api/v1/events/upcoming')->assertJsonCount(1, 'data');

        Sanctum::actingAs($admin);
        $this->deleteJson('/api/v1/events/' . $event->id)->assertOk();

        $this->getJson('/api/v1/events/upcoming')->assertJsonCount(0, 'data');
    }

    public function test_event_update_invalidates_upcoming_cache(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        $event = $this->createEvent(['status' => 'publish', 'start_date' => now()->addDays(3)]);

        $this->getJson('/api/v1/events/upcoming')->assertJsonCount(1, 'data');

        Sanctum::actingAs($admin);
        $this->putJson('/api/v1/events/' . $event->id, $this->validEventPayload(['status' => 'draft']))
            ->assertOk();

        $this->getJson('/api/v1/events/upcoming')->assertJsonCount(0, 'data');
    }

    public function test_event_cancel_invalidates_upcoming_cache(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        $event = $this->createEvent(['status' => 'publish', 'start_date' => now()->addDays(3)]);

        $this->getJson('/api/v1/events/upcoming')->assertJsonCount(1, 'data');

        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/events/' . $event->id . '/cancel')->assertOk();

        $this->getJson('/api/v1/events/upcoming')->assertJsonCount(0, 'data');
    }

    public function test_participant_registration_invalidates_upcoming_cache(): void
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $event = $this->createEvent(['price' => 0, 'quota' => 5, 'status' => 'publish', 'start_date' => now()->addDays(3)]);

        $this->getJson('/api/v1/events/upcoming')->assertJsonPath('data.0.remaining_quota', 5);

        $this->postJson('/api/v1/events/' . $event->id . '/register')->assertOk();

        $this->getJson('/api/v1/events/upcoming')->assertJsonPath('data.0.remaining_quota', 4);
    }

    public function test_upcoming_returns_empty_when_no_future_published_events(): void
    {
        $this->createEvent(['status' => 'publish', 'start_date' => now()->subDays(3)]);
        $this->createEvent(['status' => 'draft', 'start_date' => now()->addDays(3)]);

        $this->getJson('/api/v1/events/upcoming')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    private function validEventPayload(array $overrides = []): array    {
        return array_merge([
            'category_id' => $this->category->id,
            'title' => 'New Event',
            'description' => 'Deskripsi event baru',
            'location' => 'GBK',
            'address' => 'Senayan, Jakarta',
            'start_date' => now()->addDays(20)->setTime(6, 0)->toDateTimeString(),
            'end_date' => now()->addDays(20)->setTime(9, 0)->toDateTimeString(),
            'registration_start_date' => now()->subDays(3)->toDateTimeString(),
            'registration_end_date' => now()->addDays(10)->toDateTimeString(),
            'quota' => 100,
            'price' => 0,
            'is_free_for_members' => true,
            'status' => 'publish',
        ], $overrides);
    }
}
