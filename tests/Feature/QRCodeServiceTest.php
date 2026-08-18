<?php

namespace Tests\Feature;

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

    public function test_generate_writes_hash_id_into_qr_code(): void
    {
        $participant = Participant::factory()->create(['membership_type' => 'tahunan']);
        $ep = EventParticipant::factory()->create(['participant_id' => $participant->id]);

        $code = $this->service()->generate($ep);

        $this->assertSame($participant->hash_id, $code);
        $this->assertSame($participant->hash_id, $ep->fresh()->qr_code);
    }

    public function test_generate_is_identical_across_events_for_same_participant(): void
    {
        $participant = Participant::factory()->create(['membership_type' => 'tahunan']);
        $firstEp = EventParticipant::factory()->create(['participant_id' => $participant->id]);
        $secondEp = EventParticipant::factory()->create(['participant_id' => $participant->id]);

        $firstCode = $this->service()->generate($firstEp);
        $secondCode = $this->service()->generate($secondEp);

        $this->assertSame($firstCode, $secondCode);
        $this->assertSame($participant->hash_id, $firstCode);
    }
}
