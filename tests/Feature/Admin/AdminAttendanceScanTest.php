<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\GuestSponsor;
use App\Models\Participant;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceScanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Participant $participant;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($this->admin);

        $this->participant = Participant::factory()->create();

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
            'title' => 'Event Scan Test',
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

    private function register(Event $event, Participant $participant): EventParticipant
    {
        return EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'free',
            'amount' => 0,
            'payment_status' => 'confirmed',
            'qr_code' => $participant->hash_id,
        ]);
    }

    private function scanPayload(Event $event, string $qrCode): array
    {
        return [
            'event_id' => $event->id,
            'qr_code' => $qrCode,
        ];
    }

    public function test_scan_page_lists_publish_and_ongoing_events(): void
    {
        $published = $this->createEvent(['title' => 'Published Event']);
        $ongoing = $this->createEvent(['title' => 'Ongoing Event', 'status' => 'ongoing']);
        $this->createEvent(['title' => 'Draft Event', 'status' => 'draft']);

        $this->get('/admin/attendance/scan')
            ->assertOk()
            ->assertSee('Published Event')
            ->assertSee('Ongoing Event')
            ->assertDontSee('Draft Event');
    }

    public function test_process_scan_check_in_success(): void
    {
        $event = $this->createEvent();
        $registration = $this->register($event, $this->participant);

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, $this->participant->hash_id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.participant_name', $this->participant->name)
            ->assertJsonPath('data.event_title', $event->title);

        $this->assertDatabaseHas('attendances', [
            'event_participant_id' => $registration->id,
            'status' => 'present',
        ]);
        $this->assertTrue($registration->fresh()->is_attended);
    }

    public function test_process_scan_unknown_hash_id_returns_422(): void
    {
        $event = $this->createEvent();

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, '9999'))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Kode peserta tidak dikenal.');
    }

    public function test_process_scan_participant_not_registered_in_event_returns_422(): void
    {
        $event = $this->createEvent();

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, $this->participant->hash_id))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peserta tidak terdaftar di event ini.');
    }

    public function test_process_scan_duplicate_check_in_returns_422(): void
    {
        $event = $this->createEvent();
        $this->register($event, $this->participant);

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, $this->participant->hash_id))
            ->assertOk();

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, $this->participant->hash_id))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peserta sudah melakukan check-in.');
    }

    public function test_process_scan_requires_event_id(): void
    {
        $this->post('/admin/attendance/scan', [
            'qr_code' => $this->participant->hash_id,
        ])
            ->assertSessionHasErrors('event_id');
    }

    public function test_process_scan_guest_sponsor_check_in_success(): void
    {
        $event = $this->createEvent();
        $guestSponsor = GuestSponsor::factory()->create([
            'sponsor_id' => Sponsor::factory()->create()->id,
            'event_id' => $event->id,
            'qr_code' => sprintf('GS-%d-%d-0001', 1, $event->id),
        ]);

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, $guestSponsor->qr_code))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.guest_sponsor', true)
            ->assertJsonPath('data.sponsor_name', $guestSponsor->sponsor->name)
            ->assertJsonPath('data.event_title', $event->title);

        $this->assertDatabaseHas('guest_sponsor_attendances', [
            'guest_sponsor_id' => $guestSponsor->id,
            'event_id' => $event->id,
            'status' => 'present',
        ]);
    }

    public function test_process_scan_guest_sponsor_unknown_qr_returns_422(): void
    {
        $event = $this->createEvent();

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, 'GS-99-99-0001'))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'QR Code guest sponsor tidak dikenal.');
    }

    public function test_process_scan_guest_sponsor_event_mismatch_returns_422(): void
    {
        $eventA = $this->createEvent(['title' => 'Event A']);
        $eventB = $this->createEvent(['title' => 'Event B']);
        $guestSponsor = GuestSponsor::factory()->create([
            'sponsor_id' => Sponsor::factory()->create()->id,
            'event_id' => $eventB->id,
            'qr_code' => sprintf('GS-%d-%d-0001', 1, $eventB->id),
        ]);

        $this->postJson('/admin/attendance/scan', $this->scanPayload($eventA, $guestSponsor->qr_code))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'QR Code ini tidak untuk event tersebut.');

        $this->assertDatabaseMissing('guest_sponsor_attendances', [
            'guest_sponsor_id' => $guestSponsor->id,
        ]);
    }

    public function test_process_scan_guest_sponsor_duplicate_check_in_returns_422(): void
    {
        $event = $this->createEvent();
        $guestSponsor = GuestSponsor::factory()->create([
            'sponsor_id' => Sponsor::factory()->create()->id,
            'event_id' => $event->id,
            'qr_code' => sprintf('GS-%d-%d-0001', 1, $event->id),
        ]);

        $payload = $this->scanPayload($event, $guestSponsor->qr_code);

        $this->postJson('/admin/attendance/scan', $payload)->assertOk();

        $this->postJson('/admin/attendance/scan', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Guest sponsor sudah melakukan check-in.');
    }
}
