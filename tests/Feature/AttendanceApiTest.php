<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
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

    private function register(Event $event, Participant $participant): EventParticipant
    {
        return EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'free',
            'amount' => 0,
            'payment_status' => 'confirmed',
            'qr_code' => 'SH3-'.$event->id.'-'.$participant->id.'-ABC12345',
        ]);
    }

    private function checkInPayload(Event $event, Participant $participant, array $overrides = []): array
    {
        return array_merge([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'method' => 'qr_code',
        ], $overrides);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/attendance/check-in', [])->assertUnauthorized();
        $this->postJson('/api/v1/attendance/check-out', [])->assertUnauthorized();
        $this->getJson('/api/v1/attendance/report')->assertUnauthorized();
    }

    public function test_check_in_success(): void
    {
        $event = $this->createEvent();
        $registration = $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/check-in', $this->checkInPayload($event, $this->participant))
            ->assertOk()
            ->assertJsonPath('message', 'Check-in berhasil');

        $attendance = Attendance::where('event_participant_id', $registration->id)->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->check_in_time);
        $this->assertSame('present', $attendance->status);

        $this->assertTrue($registration->fresh()->is_attended);
        $this->assertNotNull($registration->fresh()->check_in_at);

        $this->assertDatabaseHas('attendance_logs', [
            'event_id' => $event->id,
            'participant_id' => $this->participant->id,
            'type' => 'check_in',
            'scanned_by' => $this->user->id,
            'qr_code' => $registration->qr_code,
        ]);
    }

    public function test_check_in_requires_registration(): void
    {
        $event = $this->createEvent();

        $this->postJson('/api/v1/attendance/check-in', $this->checkInPayload($event, $this->participant))
            ->assertUnprocessable()
            ->assertJsonPath('errors.participant.0', 'Peserta tidak terdaftar di event ini.');
    }

    public function test_check_in_duplicate_returns_422(): void
    {
        $event = $this->createEvent();
        $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/check-in', $this->checkInPayload($event, $this->participant))
            ->assertOk();

        $this->postJson('/api/v1/attendance/check-in', $this->checkInPayload($event, $this->participant))
            ->assertUnprocessable()
            ->assertJsonPath('errors.participant.0', 'Peserta sudah melakukan check-in.');
    }

    public function test_check_in_rejects_invalid_method(): void
    {
        $event = $this->createEvent();
        $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/check-in', $this->checkInPayload($event, $this->participant, ['method' => 'face']))
            ->assertUnprocessable();
    }

    public function test_check_out_without_check_in_returns_422(): void
    {
        $event = $this->createEvent();
        $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/check-out', [
            'event_id' => $event->id,
            'participant_id' => $this->participant->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.participant.0', 'Peserta belum melakukan check-in.');
    }

    public function test_check_out_success(): void
    {
        $event = $this->createEvent();
        $registration = $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/check-in', $this->checkInPayload($event, $this->participant))
            ->assertOk();

        $this->postJson('/api/v1/attendance/check-out', [
            'event_id' => $event->id,
            'participant_id' => $this->participant->id,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Check-out berhasil');

        $attendance = Attendance::where('event_participant_id', $registration->id)->first();

        $this->assertNotNull($attendance->check_out_time);
        $this->assertNotNull($registration->fresh()->check_out_at);

        $this->assertDatabaseHas('attendance_logs', [
            'event_id' => $event->id,
            'participant_id' => $this->participant->id,
            'type' => 'check_out',
            'scanned_by' => $this->user->id,
        ]);
    }

    public function test_scan_without_event_returns_participant_and_registered_events(): void
    {
        $event = $this->createEvent();
        $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/scan', [
            'qr_code' => $this->participant->participant_code,
        ])
            ->assertOk()
            ->assertJsonPath('data.participant_code', $this->participant->participant_code)
            ->assertJsonPath('data.name', $this->participant->name)
            ->assertJsonPath('data.status', 'non_member')
            ->assertJsonPath('data.registered_events.0.event_id', $event->id)
            ->assertJsonPath('data.registered_events.0.event_title', $event->title);
    }

    public function test_scan_with_event_returns_registration_status(): void
    {
        $event = $this->createEvent();
        $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/scan', [
            'event_id' => $event->id,
            'qr_code' => $this->participant->participant_code,
        ])
            ->assertOk()
            ->assertJsonPath('data.event_id', $event->id)
            ->assertJsonPath('data.participant_id', $this->participant->id)
            ->assertJsonPath('data.participant_code', $this->participant->participant_code)
            ->assertJsonPath('data.registration_status', 'confirmed')
            ->assertJsonPath('data.is_attended', false);
    }

    public function test_scan_with_event_not_registered_returns_422(): void
    {
        $event = $this->createEvent();

        $this->postJson('/api/v1/attendance/scan', [
            'event_id' => $event->id,
            'qr_code' => $this->participant->participant_code,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.participant.0', 'Peserta tidak terdaftar di event ini.');
    }

    public function test_scan_unknown_code_returns_422(): void
    {
        $this->postJson('/api/v1/attendance/scan', ['qr_code' => '9999'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.participant.0', 'Kode peserta tidak dikenal.');
    }

    public function test_scan_legacy_sh3_code_returns_422(): void
    {
        $this->postJson('/api/v1/attendance/scan', ['qr_code' => 'SH3-1-2-ABC12345'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.qr_code.0', 'QR Code tidak valid.');
    }

    public function test_scan_invalid_qr_returns_422(): void
    {
        $this->postJson('/api/v1/attendance/scan', ['qr_code' => 'invalid-code'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.qr_code.0', 'QR Code tidak valid.');
    }

    public function test_report_returns_attendance_stats(): void
    {
        $event = $this->createEvent();
        $presentParticipant = Participant::factory()->create();
        $absentParticipant = Participant::factory()->create();

        $this->register($event, $presentParticipant);
        $this->register($event, $absentParticipant);

        $this->postJson('/api/v1/attendance/check-in', $this->checkInPayload($event, $presentParticipant))
            ->assertOk();

        $this->getJson('/api/v1/attendance/report')
            ->assertOk()
            ->assertJsonPath('data.0.event_id', $event->id)
            ->assertJsonPath('data.0.registered', 2)
            ->assertJsonPath('data.0.present', 1)
            ->assertJsonPath('data.0.absent', 1)
            ->assertJsonPath('data.0.attendance_rate', 50);
    }

    public function test_report_filters_by_event(): void
    {
        $firstEvent = $this->createEvent();
        $secondEvent = $this->createEvent(['title' => 'Second Event']);
        $this->register($firstEvent, $this->participant);

        $this->getJson('/api/v1/attendance/report?event_id='.$secondEvent->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_id', $secondEvent->id)
            ->assertJsonPath('data.0.registered', 0);
    }

    public function test_sync_up_processes_batch_and_is_idempotent(): void
    {
        $event = $this->createEvent();
        $registration = $this->register($event, $this->participant);

        $attendances = [
            [
                'event_id' => $event->id,
                'qr_code' => $registration->qr_code,
                'check_in_time' => now()->toDateTimeString(),
                'check_out_time' => null,
            ],
        ];

        $this->postJson('/api/v1/attendance/sync-up', ['attendances' => $attendances])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Event attendance synced successfully');

        $this->assertTrue($registration->fresh()->is_attended);
        $this->assertNotNull($registration->fresh()->check_in_at);

        Attendance::create([
            'event_participant_id' => $registration->id,
            'check_in_time' => now(),
            'status' => 'present',
        ]);

        $this->postJson('/api/v1/attendance/sync-up', ['attendances' => $attendances])
            ->assertOk()
            ->assertJsonPath('synced_attendance_count', 1);
    }

    public function test_sync_up_skips_unregistered_participant(): void
    {
        $event = $this->createEvent();

        $this->postJson('/api/v1/attendance/sync-up', ['attendances' => [
            [
                'event_id' => $event->id,
                'qr_code' => 'NONEXISTENT-QR-CODE',
                'check_in_time' => now()->toDateTimeString(),
            ],
        ]])
            ->assertOk()
            ->assertJsonPath('synced_attendance_count', 0)
            ->assertJsonPath('synced_ots_count', 0);
    }

    public function test_sync_up_processes_ots_registration(): void
    {
        $event = $this->createEvent();
        $member = Participant::create([
            'hash_id' => 'SH3TEST123',
            'name' => 'OTS Member',
            'email' => 'ots@test.com',
            'phone' => '081000000000',
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/attendance/sync-up', ['ots_registrations' => [
            [
                'event_id' => $event->id,
                'hash_id' => 'SH3TEST123',
                'member_name' => 'OTS Member',
                'check_in_time' => now()->toDateTimeString(),
            ],
        ]])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('synced_ots_count', 1);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'participant_id' => $member->id,
            'qr_code' => 'OTS-SH3TEST123EV'.$event->id,
            'payment_status' => 'confirmed',
        ]);
    }

    public function test_sync_down_returns_delta_since_timestamp(): void
    {
        $event = $this->createEvent();
        $registration = $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/check-in', $this->checkInPayload($event, $this->participant))
            ->assertOk();

        $attendance = Attendance::where('event_participant_id', $registration->id)->first();
        $attendance->update(['updated_at' => now()->subMinutes(5)]);

        $this->getJson('/api/v1/attendance/sync-down?event_id='.$event->id.'&since='.now()->toISOString())
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/attendance/sync-down?event_id='.$event->id.'&since='.now()->subHours(1)->toISOString())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_id', $event->id)
            ->assertJsonPath('data.0.participant_id', $this->participant->id)
            ->assertJsonPath('data.0.status', 'present');
    }

    public function test_sync_down_returns_all_when_no_filter(): void
    {
        $event = $this->createEvent();
        $this->register($event, $this->participant);

        $this->postJson('/api/v1/attendance/check-in', $this->checkInPayload($event, $this->participant))
            ->assertOk();

        $this->getJson('/api/v1/attendance/sync-down')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_rejected_registration_qr_cannot_check_in(): void
    {
        $event = $this->createEvent();
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $this->participant->id,
            'registration_type' => 'paid',
            'amount' => 100000,
            'payment_status' => 'rejected',
            'qr_code' => 'SH3-'.$event->id.'-'.$this->participant->id.'-OLDQR',
        ]);

        $this->postJson('/api/v1/attendance/check-in', $this->checkInPayload($event, $this->participant))
            ->assertUnprocessable()
            ->assertJsonPath('errors.participant.0', 'Pendaftaran ini ditolak/dibatalkan dan tidak dapat digunakan untuk check-in.');

        $this->assertDatabaseMissing('attendance_logs', [
            'event_id' => $event->id,
            'participant_id' => $this->participant->id,
            'type' => 'check_in',
        ]);
    }

    public function test_refunded_registration_qr_cannot_check_in(): void
    {
        $event = $this->createEvent();
        EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $this->participant->id,
            'registration_type' => 'paid',
            'amount' => 100000,
            'payment_status' => 'refunded',
            'qr_code' => 'SH3-'.$event->id.'-'.$this->participant->id.'-OLDQR',
        ]);

        $this->postJson('/api/v1/attendance/check-in', $this->checkInPayload($event, $this->participant))
            ->assertUnprocessable()
            ->assertJsonPath('errors.participant.0', 'Pendaftaran ini ditolak/dibatalkan dan tidak dapat digunakan untuk check-in.');
    }
}
