<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
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
            'qr_code' => $participant->participant_code,
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

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, $this->participant->participant_code))
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

    public function test_process_scan_unknown_participant_code_returns_422(): void
    {
        $event = $this->createEvent();

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, '9999'))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Kode peserta tidak dikenal.');
    }

    public function test_process_scan_participant_not_registered_in_event_returns_422(): void
    {
        $event = $this->createEvent();

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, $this->participant->participant_code))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peserta tidak terdaftar di event ini.');
    }

    public function test_process_scan_duplicate_check_in_returns_422(): void
    {
        $event = $this->createEvent();
        $this->register($event, $this->participant);

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, $this->participant->participant_code))
            ->assertOk();

        $this->postJson('/admin/attendance/scan', $this->scanPayload($event, $this->participant->participant_code))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peserta sudah melakukan check-in.');
    }

    public function test_process_scan_requires_event_id(): void
    {
        $this->post('/admin/attendance/scan', [
            'qr_code' => $this->participant->participant_code,
        ])
            ->assertSessionHasErrors('event_id');
    }
}
