<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'qr_code' => 'SH3-'.$event->id.'-'.$participant->id.'-ABC12345',
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
            'qr_code' => 'SH3-'.$event->id.'-'.$participant->id.'-ABC12345',
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
            'qr_code' => 'SH3-'.$event->id.'-'.$participant->id.'-ABC12345',
        ]);

        $this->getJson('/api/v1/events/'.$event->id.'/qr')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.participant_name', $participant->name)
            ->assertJsonPath('data.0.qr_code', 'SH3-'.$event->id.'-'.$participant->id.'-ABC12345');
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

    private function validEventPayload(array $overrides = []): array
    {
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
