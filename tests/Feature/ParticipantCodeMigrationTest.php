<?php

namespace Tests\Feature;

use App\Services\ParticipantCodeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParticipantCodeMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_backfill_assigns_sequential_codes_per_status_ordered_by_id(): void
    {
        $this->migrateDownToPreBackfillState();

        $this->insertRow(['name' => 'Active Member', 'membership_type' => 'tahunan', 'membership_end_date' => now()->addDay()->toDateString(), 'hash_id' => 'SH3ACTIVE']);
        $this->insertRow(['name' => 'Expired Member', 'membership_type' => 'tahunan', 'membership_end_date' => now()->subDay()->toDateString(), 'hash_id' => 'SH3EXPIRED']);
        $this->insertRow(['name' => 'Non Member', 'membership_type' => 'none', 'membership_end_date' => null, 'hash_id' => 'SH3NONMEMBER']);
        $this->insertRow(['name' => 'OTS Aggregator', 'membership_type' => 'none', 'membership_end_date' => null, 'hash_id' => 'MANUAL_OTS_AGGREGATOR']);

        $this->runMigration();

        $this->assertFalse(Schema::hasColumn('participants', 'hash_id'));

        $codes = DB::table('participants')->orderBy('id')->pluck('participant_code')->all();

        $this->assertSame(['0001', 'NM0001', 'NM0002', 'NM0000'], $codes);
        $this->assertCount(4, array_unique($codes));
        $this->assertSame(0, DB::table('participants')->whereNull('participant_code')->count());
    }

    public function test_sentinel_reserved_code_does_not_consume_the_sequence(): void
    {
        $this->migrateDownToPreBackfillState();

        $this->insertRow(['name' => 'Non Member One', 'membership_type' => 'none', 'hash_id' => 'SH3N1']);
        $this->insertRow(['name' => 'OTS Aggregator', 'membership_type' => 'none', 'hash_id' => 'MANUAL_OTS_AGGREGATOR']);
        $this->insertRow(['name' => 'Non Member Two', 'membership_type' => 'none', 'hash_id' => 'SH3N2']);

        $this->runMigration();

        $this->assertFalse(Schema::hasColumn('participants', 'hash_id'));

        $codes = DB::table('participants')->orderBy('id')->pluck('participant_code')->all();

        $this->assertSame(['NM0001', 'NM0000', 'NM0002'], $codes);
        $this->assertSame('NM0003', app(ParticipantCodeService::class)->next('NM'));
    }

    public function test_membership_type_none_is_treated_as_non_member_even_with_future_end_date(): void
    {
        $this->migrateDownToPreBackfillState();

        $this->insertRow(['name' => 'No Membership', 'membership_type' => 'none', 'membership_end_date' => now()->addYear()->toDateString(), 'hash_id' => 'SH3NO']);
        $this->insertRow(['name' => 'Active Member', 'membership_type' => 'tahunan', 'membership_end_date' => now()->addDay()->toDateString(), 'hash_id' => 'SH3ACTIVE']);

        $this->runMigration();

        $this->assertFalse(Schema::hasColumn('participants', 'hash_id'));

        $codes = DB::table('participants')->orderBy('id')->pluck('participant_code')->all();

        $this->assertSame(['NM0001', '0001'], $codes);
    }

    private function migrateDownToPreBackfillState(): void
    {
        if (! Schema::hasColumn('participants', 'hash_id')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->string('hash_id')->nullable()->unique()->after('user_id');
            });
        }

        Schema::table('participants', function (Blueprint $table) {
            $table->dropUnique(['participant_code']);
            $table->dropColumn('participant_code');
        });
    }

    private function runMigration(): void
    {
        $migration = require database_path('migrations/2026_08_17_000002_add_participant_code_to_participants_table.php');
        $migration->up();
    }

    private function insertRow(array $overrides): void
    {
        DB::table('participants')->insert(array_merge([
            'name' => 'Test Participant',
            'email' => fake()->unique()->safeEmail(),
            'membership_type' => 'none',
            'is_active' => true,
            'total_events_participated' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
