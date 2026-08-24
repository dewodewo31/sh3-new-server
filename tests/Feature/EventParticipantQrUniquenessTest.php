<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\User;
use App\Services\QRCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventParticipantQrUniquenessTest extends TestCase
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
            'title' => 'Event',
            'description' => 'Deskripsi',
            'location' => 'Monas',
            'address' => 'Jakarta',
            'start_date' => now()->addDays(10)->setTime(6, 0),
            'end_date' => now()->addDays(10)->setTime(9, 0),
            'registration_start_date' => now()->subDays(5),
            'registration_end_date' => now()->addDays(5),
            'quota' => 20,
            'price' => 0,
            'is_free_for_members' => true,
            'status' => 'publish',
        ], $overrides));
    }

    private function makeParticipantWithUser(array $participantOverrides = []): array
    {
        $user = User::factory()->create(['role' => 'participant']);
        $participant = Participant::factory()->create(array_merge(
            ['user_id' => $user->id],
            $participantOverrides,
        ));

        return [$user, $participant];
    }

    private function registerViaApi(Event $event, User $user): array
    {
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/events/'.$event->id.'/register');

        $participant = Participant::where('user_id', $user->id)->firstOrFail();
        $registration = EventParticipant::where('event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->firstOrFail();

        return [$response, $registration];
    }

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin_full_access']);
    }

    // 1 & 4. Same participant, different events => unique QR + hash_id unchanged.
    public function test_same_participant_different_events_get_unique_qr_and_identity_unchanged(): void
    {
        [$user, $participant] = $this->makeParticipantWithUser();
        $originalHash = $participant->hash_id;

        $codes = [];
        for ($i = 0; $i < 5; $i++) {
            $event = $this->createEvent(['title' => 'Event '.$i]);
            [$response, $registration] = $this->registerViaApi($event, $user);

            $response->assertOk();
            $qr = $response->json('data.qr_code');
            $this->assertMatchesRegularExpression('/^SH3-\d+-\d{2}-[A-Z0-9]{6}$/', $qr);
            $this->assertSame($qr, $registration->qr_code);
            $codes[] = $qr;
        }

        $this->assertSame(5, count($codes));
        $this->assertSame(5, count(array_unique($codes)), 'All QR codes must be unique per event.');
        $this->assertSame($originalHash, $participant->fresh()->hash_id);
    }

    // 2. QR format.
    public function test_qr_code_format(): void
    {
        [$user, $participant] = $this->makeParticipantWithUser();
        $event = $this->createEvent();
        [, $registration] = $this->registerViaApi($event, $user);

        $this->assertMatchesRegularExpression('/^SH3-\d+-\d{2}-[A-Z0-9]{6}$/', $registration->qr_code);
    }

    // 3. QR uniqueness across many registrations.
    public function test_qr_uniqueness_across_many_registrations(): void
    {
        $codes = [];
        for ($i = 0; $i < 15; $i++) {
            [$user, $participant] = $this->makeParticipantWithUser();
            $event = $this->createEvent();
            [, $registration] = $this->registerViaApi($event, $user);
            $codes[] = $registration->qr_code;
        }

        $this->assertSame(15, count($codes));
        $this->assertSame(15, count(array_unique($codes)));
    }

    // 4 (explicit). Participant hash unchanged when QR is generated.
    public function test_participant_hash_id_unchanged_after_qr_generation(): void
    {
        [$user, $participant] = $this->makeParticipantWithUser();
        $event = $this->createEvent();
        $before = $participant->hash_id;

        app(QRCodeService::class)->generate(
            EventParticipant::factory()->create([
                'event_id' => $event->id,
                'participant_id' => $participant->id,
            ])
        );

        $this->assertSame($before, $participant->fresh()->hash_id);
    }

    // 5. Check-in via QR finds the correct event participant.
    public function test_check_in_via_qr_finds_correct_registration(): void
    {
        [$user, $participant] = $this->makeParticipantWithUser();
        $event = $this->createEvent();
        [, $registration] = $this->registerViaApi($event, $user);

        $this->actingAs($this->adminUser());

        $this->postJson('/admin/attendance/scan', [
            'event_id' => $event->id,
            'qr_code' => $registration->qr_code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.participant_name', $participant->name);

        $this->assertTrue($registration->fresh()->is_attended);
    }

    // 6. QR from Event A cannot be used to check-in Event B.
    public function test_qr_from_event_a_rejected_for_event_b(): void
    {
        [$user, $participant] = $this->makeParticipantWithUser();
        $eventA = $this->createEvent(['title' => 'Event A']);
        $eventB = $this->createEvent(['title' => 'Event B']);
        [, $registrationA] = $this->registerViaApi($eventA, $user);

        $this->actingAs($this->adminUser());

        $this->postJson('/admin/attendance/scan', [
            'event_id' => $eventB->id,
            'qr_code' => $registrationA->qr_code,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'QR Code ini tidak untuk event tersebut.');
    }

    // 7. Invalid QR is rejected.
    public function test_invalid_qr_is_rejected(): void
    {
        $event = $this->createEvent();
        $this->actingAs($this->adminUser());

        $this->postJson('/admin/attendance/scan', [
            'event_id' => $event->id,
            'qr_code' => 'SH3-8-26-INVALID',
        ])->assertUnprocessable();
    }

    // 8. Already checked-in behavior preserved.
    public function test_already_checked_in_qr_is_rejected(): void
    {
        [$user, $participant] = $this->makeParticipantWithUser();
        $event = $this->createEvent();
        [, $registration] = $this->registerViaApi($event, $user);

        $this->actingAs($this->adminUser());

        $this->postJson('/admin/attendance/scan', [
            'event_id' => $event->id,
            'qr_code' => $registration->qr_code,
        ])->assertOk();

        $this->postJson('/admin/attendance/scan', [
            'event_id' => $event->id,
            'qr_code' => $registration->qr_code,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peserta sudah melakukan check-in.');
    }

    // 9. Payment status validation preserved (rejected registration cannot check-in).
    public function test_rejected_registration_qr_cannot_check_in(): void
    {
        [$user, $participant] = $this->makeParticipantWithUser();
        $event = $this->createEvent();

        $registration = EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_type' => 'paid',
            'amount' => 100000,
            'payment_status' => 'rejected',
            'qr_code' => null,
        ]);
        app(QRCodeService::class)->generate($registration);

        $this->actingAs($this->adminUser());

        $this->postJson('/admin/attendance/scan', [
            'event_id' => $event->id,
            'qr_code' => $registration->qr_code,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Pendaftaran ini ditolak/dibatalkan dan tidak dapat digunakan untuk check-in.');
    }

    // 10. API registration returns qr_code + ticket_code as the unique token.
    public function test_api_registration_returns_unique_qr_and_ticket_code(): void
    {
        [$user, $participant] = $this->makeParticipantWithUser();
        $event = $this->createEvent();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/events/'.$event->id.'/register');

        $response->assertOk()
            ->assertJsonPath('data.qr_code', fn ($qr) => (bool) preg_match('/^SH3-\d+-\d{2}-[A-Z0-9]{6}$/', $qr))
            ->assertJsonPath('data.ticket_code', fn ($tc) => (bool) preg_match('/^SH3-\d+-\d{2}-[A-Z0-9]{6}$/', $tc))
            ->assertJsonPath('data.qr_code', $response->json('data.ticket_code'));
    }
}
