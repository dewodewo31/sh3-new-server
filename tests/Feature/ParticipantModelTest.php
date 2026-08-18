<?php

namespace Tests\Feature;

use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_member_participant_gets_nm_prefixed_code(): void
    {
        $participant = Participant::factory()->create();

        $this->assertMatchesRegularExpression('/^NM\d{4}$/', $participant->hash_id);
    }

    public function test_member_participant_gets_digit_only_code(): void
    {
        $participant = Participant::factory()->create(['membership_type' => 'tahunan']);

        $this->assertMatchesRegularExpression('/^\d{4}$/', $participant->hash_id);
    }

    public function test_codes_are_unique_and_sequential_per_prefix(): void
    {
        $memberA = Participant::factory()->create(['membership_type' => 'tahunan']);
        $memberB = Participant::factory()->create(['membership_type' => 'tahunan']);
        $nonMember = Participant::factory()->create();

        $this->assertSame('0001', $memberA->hash_id);
        $this->assertSame('0002', $memberB->hash_id);
        $this->assertSame('NM0001', $nonMember->hash_id);
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
}
