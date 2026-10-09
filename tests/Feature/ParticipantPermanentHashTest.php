<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Services\MembershipService;
use Database\Seeders\MembershipPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Permanent Member Hash ID lifecycle: the hash is generated exactly once and
 * must NEVER change across expire / cancel / renew / plan change. Only the
 * displayed ID switches between the permanent hash and the NM code.
 */
class ParticipantPermanentHashTest extends TestCase
{
    use RefreshDatabase;

    private Participant $participant;

    private MembershipService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MembershipPlanSeeder::class);

        $this->participant = Participant::factory()->create(['membership_type' => 'none']);
        $this->service = app(MembershipService::class);
    }

    public function test_permanent_hash_generated_once_and_never_regenerated(): void
    {
        $hash = $this->participant->hash_id;

        $this->assertMatchesRegularExpression('/^\d{4}$/', $hash);
        $this->assertMatchesRegularExpression('/^NM\d{4}$/', $this->participant->non_member_code);

        $this->participant->update(['name' => 'Renamed']);
        $this->participant->refresh();

        $this->assertSame($hash, $this->participant->fresh()->hash_id);
        $this->assertSame($this->participant->non_member_code, $this->participant->fresh()->non_member_code);
    }

    public function test_activation_displays_permanent_hash(): void
    {
        $hash = $this->participant->hash_id;

        $this->service->grant($this->participant, 'tahunan');
        $this->participant->refresh();

        $this->assertTrue($this->participant->isMembershipActive());
        $this->assertSame($hash, $this->participant->hash_id);
        $this->assertSame($hash, $this->participant->displayMemberId());
    }

    public function test_expiration_displays_nm_code_but_hash_unchanged(): void
    {
        $hash = $this->participant->hash_id;

        $this->service->grant($this->participant, 'mingguan');
        $this->expire();
        $this->participant->refresh();

        $this->assertFalse($this->participant->isMembershipActive());
        $this->assertSame($hash, $this->participant->hash_id);
        $this->assertSame($this->participant->non_member_code, $this->participant->displayMemberId());
    }

    public function test_renewal_reuses_the_same_permanent_hash(): void
    {
        $hash = $this->participant->hash_id;

        $this->service->grant($this->participant, 'mingguan');
        $this->expire();
        $this->service->grant($this->participant, 'tahunan');
        $this->participant->refresh();

        $this->assertTrue($this->participant->isMembershipActive());
        $this->assertSame($hash, $this->participant->hash_id);
        $this->assertSame($hash, $this->participant->displayMemberId());
    }

    public function test_cancellation_displays_nm_code_but_hash_unchanged(): void
    {
        $hash = $this->participant->hash_id;

        $this->service->grant($this->participant, 'tahunan');
        $this->service->cancelMembership($this->participant, 'test');
        $this->participant->refresh();

        $this->assertFalse($this->participant->isMembershipActive());
        $this->assertSame($hash, $this->participant->hash_id);
        $this->assertSame($this->participant->non_member_code, $this->participant->displayMemberId());
    }

    public function test_plan_change_keeps_permanent_hash(): void
    {
        $hash = $this->participant->hash_id;

        $this->service->grant($this->participant, 'tahunan');
        $this->service->grant($this->participant, 'setengah_tahun');
        $this->participant->refresh();

        $this->assertSame('setengah_tahun', $this->participant->membership_type);
        $this->assertTrue($this->participant->isMembershipActive());
        $this->assertSame($hash, $this->participant->hash_id);
        $this->assertSame($hash, $this->participant->displayMemberId());
    }

    public function test_hash_is_stable_across_multiple_membership_cycles(): void
    {
        $hash = $this->participant->hash_id;

        // Active -> Expired -> Renew -> Expired -> Renew -> Cancelled -> Renew.
        $this->service->grant($this->participant, 'mingguan');
        $this->assertCycleHash($hash);

        $this->expire();
        $this->assertCycleHash($hash);

        $this->service->grant($this->participant, 'tahunan');
        $this->assertCycleHash($hash);

        $this->expire();
        $this->assertCycleHash($hash);

        $this->service->grant($this->participant, 'mingguan');
        $this->assertCycleHash($hash);

        $this->service->cancelMembership($this->participant, 'test');
        $this->assertCycleHash($hash);

        $this->service->grant($this->participant, 'tahunan');
        $this->assertCycleHash($hash);

        $this->assertTrue($this->participant->isMembershipActive());
    }

    public function test_hash_and_non_member_codes_are_unique(): void
    {
        $participants = Participant::factory()->count(3)->create();

        $this->assertCount(3, $participants->pluck('hash_id')->unique());
        $this->assertCount(3, $participants->pluck('non_member_code')->unique());
    }

    public function test_backfill_converts_legacy_nm_hash_and_assigns_non_member_code(): void
    {
        // Simulate legacy rows (created before the migration, bypassing hooks).
        DB::table('participants')->insert([
            [
                'name' => 'Legacy NM Participant',
                'email' => 'legacy.nm@example.com',
                'membership_type' => 'none',
                'hash_id' => 'NM0100',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Legacy Member',
                'email' => 'legacy.member@example.com',
                'membership_type' => 'none',
                'hash_id' => '9900',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Manual OTS NON MEMBER',
                'email' => 'legacy.ots@example.com',
                'membership_type' => 'none',
                'hash_id' => 'NM0000',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $migration = require database_path('migrations/2026_10_08_000001_add_non_member_code_to_participants_table.php');
        $migration->up();

        $legacyNm = Participant::where('email', 'legacy.nm@example.com')->first();
        $legacyMember = Participant::where('email', 'legacy.member@example.com')->first();
        $aggregator = Participant::where('email', 'legacy.ots@example.com')->first();

        // Former NM identity becomes the permanent non_member_code display;
        // the participant receives a fresh permanent numeric member hash.
        $this->assertSame('NM0100', $legacyNm->non_member_code);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $legacyNm->hash_id);

        $this->assertMatchesRegularExpression('/^NM\d{4}$/', $legacyMember->non_member_code);
        $this->assertSame('9900', $legacyMember->hash_id);

        // OTS aggregator keeps its NM marker in hash_id.
        $this->assertSame('NM0000', $aggregator->hash_id);
        $this->assertSame('NM0000', $aggregator->non_member_code);

        // No duplicates anywhere.
        $this->assertCount(
            Participant::count(),
            Participant::pluck('hash_id')->unique()
        );
        $this->assertCount(
            Participant::count(),
            Participant::pluck('non_member_code')->unique()
        );
    }

    private function expire(): void
    {
        $this->participant->membershipHistories()
            ->where('status', 'active')
            ->update([
                'status' => 'expired',
                'end_date' => now()->subDay()->toDateString(),
            ]);

        $this->participant->update([
            'membership_end_date' => now()->subDay()->toDateString(),
        ]);
    }

    private function assertCycleHash(string $hash): void
    {
        $this->participant->refresh();

        $this->assertSame($hash, $this->participant->hash_id, 'Permanent member hash must never change');
    }
}
