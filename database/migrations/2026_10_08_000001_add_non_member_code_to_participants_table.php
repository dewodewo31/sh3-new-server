<?php

use App\Services\ParticipantCodeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Legacy pseudo-participant for manual OTS aggregation. Its NM-format
     * hash_id is the marker used by offline sync payloads, so it keeps the
     * hash in place and only receives a matching non_member_code.
     */
    private const OTS_AGGREGATOR_CODE = 'NM0000';

    public function up(): void
    {
        if (! Schema::hasColumn('participants', 'non_member_code')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->string('non_member_code')->nullable()->after('hash_id');
            });
        }

        $this->backfill();

        $raw = DB::select("SHOW INDEX FROM participants WHERE Key_name = 'participants_non_member_code_unique'");
        if (empty($raw)) {
            Schema::table('participants', function (Blueprint $table) {
                $table->unique('non_member_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('participants', 'non_member_code')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->dropColumn('non_member_code');
            });
        }
    }

    /**
     * Idempotent conversion to the permanent identity design:
     * - hash_id in NM format (assigned because membership was inactive at
     *   creation/backfill time) moves to non_member_code and the participant
     *   receives a fresh PERMANENT numeric member hash.
     * - every other participant receives a non_member_code for inactive
     *   display.
     * Neither value is ever regenerated afterwards.
     */
    private function backfill(): void
    {
        $codes = app(ParticipantCodeService::class);

        DB::table('participants')
            ->whereNull('non_member_code')
            ->orderBy('id')
            ->chunkById(500, function ($participants) use ($codes) {
                foreach ($participants as $participant) {
                    if ($participant->hash_id === self::OTS_AGGREGATOR_CODE) {
                        DB::table('participants')->where('id', $participant->id)->update([
                            'non_member_code' => self::OTS_AGGREGATOR_CODE,
                        ]);

                        continue;
                    }

                    if (preg_match('/^NM\d{4}$/', (string) $participant->hash_id)) {
                        DB::table('participants')->where('id', $participant->id)->update([
                            'non_member_code' => $participant->hash_id,
                            'hash_id' => $codes->next(''),
                        ]);

                        continue;
                    }

                    DB::table('participants')->where('id', $participant->id)->update([
                        'non_member_code' => $codes->next('NM'),
                    ]);
                }
            });
    }
};
