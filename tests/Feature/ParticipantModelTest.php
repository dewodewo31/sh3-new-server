<?php

namespace Tests\Feature;

use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_always_gets_permanent_numeric_hash_regardless_of_status(): void
    {
        $nonMember = Participant::factory()->create();
        $member = Participant::factory()->create(['membership_type' => 'tahunan']);

        $this->assertMatchesRegularExpression('/^\d{4}$/', $nonMember->hash_id);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $member->hash_id);

        $this->assertMatchesRegularExpression('/^NM\d{4}$/', $nonMember->non_member_code);
        $this->assertMatchesRegularExpression('/^NM\d{4}$/', $member->non_member_code);
    }

    public function test_codes_are_unique_and_sequential_per_prefix(): void
    {
        $memberA = Participant::factory()->create(['membership_type' => 'tahunan']);
        $memberB = Participant::factory()->create(['membership_type' => 'tahunan']);

        $this->assertSame('0001', $memberA->hash_id);
        $this->assertSame('0002', $memberB->hash_id);
        $this->assertSame('NM0001', $memberA->non_member_code);
        $this->assertSame('NM0002', $memberB->non_member_code);
    }

    public function test_ots_aggregator_sentinel_constant_exists(): void
    {
        $this->assertSame('NM0000', Participant::OTS_AGGREGATOR_CODE);
    }

    public function test_hash_id_is_exposed(): void
    {
        $participant = Participant::factory()->create();

        $this->assertArrayHasKey('hash_id', $participant->toArray());
        $this->assertSame($participant->hash_id, $participant->toArray()['hash_id']);
    }

    public function test_non_member_displays_nm_code(): void
    {
        $participant = Participant::factory()->create();

        $this->assertSame($participant->non_member_code, $participant->displayMemberId());
        $this->assertNotSame($participant->hash_id, $participant->displayMemberId());
    }
}
