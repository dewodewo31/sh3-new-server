<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Services\QRCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QRCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): QRCodeService
    {
        return app(QRCodeService::class);
    }

    public function test_decode_member_code(): void
    {
        $this->assertSame(
            ['hash_id' => '3950', 'status' => 'member'],
            $this->service()->decode('3950'),
        );
    }

    public function test_decode_non_member_code(): void
    {
        $this->assertSame(
            ['hash_id' => 'NM0001', 'status' => 'non_member'],
            $this->service()->decode('NM0001'),
        );
    }

    public function test_decode_rejects_legacy_sh3_format(): void
    {
        $this->assertNull($this->service()->decode('SH3-1-2-ABC'));
    }

    public function test_decode_rejects_unrecognized_string(): void
    {
        $this->assertNull($this->service()->decode('ABC123'));
    }

    public function test_decode_rejects_three_digit_code(): void
    {
        $this->assertNull($this->service()->decode('NM00'));
    }

    public function test_generate_emits_unique_sh3_ticket_code(): void
    {
        $event = Event::factory()->create();
        $participant = Participant::factory()->create(['membership_type' => 'tahunan']);
        $ep = EventParticipant::factory()->create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
        ]);

        $code = $this->service()->generate($ep);

        $this->assertMatchesRegularExpression('/^SH3-\d+-\d{2}-[A-Z0-9]{6}$/', $code);
        $this->assertSame($code, $ep->fresh()->qr_code);
        $this->assertNotSame($participant->hash_id, $code);
    }

    public function test_generate_is_unique_per_event_for_same_participant(): void
    {
        $eventA = Event::factory()->create();
        $eventB = Event::factory()->create();
        $participant = Participant::factory()->create(['membership_type' => 'tahunan']);
        $firstEp = EventParticipant::factory()->create([
            'event_id' => $eventA->id,
            'participant_id' => $participant->id,
        ]);
        $secondEp = EventParticipant::factory()->create([
            'event_id' => $eventB->id,
            'participant_id' => $participant->id,
        ]);

        $firstCode = $this->service()->generate($firstEp);
        $secondCode = $this->service()->generate($secondEp);

        $this->assertNotSame($firstCode, $secondCode);
        $this->assertNotSame($participant->hash_id, $firstCode);
        $this->assertNotSame($participant->hash_id, $secondCode);
    }

    public function test_decode_accepts_sh3_ticket_format(): void
    {
        $this->assertSame(
            ['qr_code' => 'SH3-8-26-X7K92P'],
            $this->service()->decode('SH3-8-26-X7K92P'),
        );
    }
}
