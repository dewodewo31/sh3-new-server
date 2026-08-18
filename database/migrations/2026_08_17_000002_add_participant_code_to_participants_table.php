<?php

use App\Services\ParticipantCodeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OTS_AGGREGATOR_HASH_ID = 'MANUAL_OTS_AGGREGATOR';

    private const OTS_AGGREGATOR_CODE = 'NM0000';

    public function up(): void
    {
        // Backfill: overwrite old SH3xxxxxxxx values with sequential format.
        DB::table('participants')
            ->orderBy('id')
            ->chunkById(500, function ($participants) {
                foreach ($participants as $participant) {
                    DB::table('participants')->where('id', $participant->id)->update([
                        'hash_id' => $this->codeFor($participant),
                    ]);
                }
            });

        Schema::table('participants', function (Blueprint $table) {
            $table->string('hash_id')->nullable(false)->change();
        });

        // Add unique constraint only if it doesn't already exist (000100 may have added it).
        $raw = DB::select("SHOW INDEX FROM participants WHERE Key_name = 'participants_hash_id_unique'");
        if (empty($raw)) {
            Schema::table('participants', function (Blueprint $table) {
                $table->unique('hash_id');
            });
        }
    }

    public function down(): void
    {
        // Data migration: old SH3xxxxxxxx values are overwritten and cannot be restored.
        // No-op to avoid conflicts with 000001_rename's down() which may have already
        // renamed hash_id → participant_code (carrying the unique index name with it).
    }

    private function codeFor(object $participant): string
    {
        if ($participant->hash_id === self::OTS_AGGREGATOR_HASH_ID) {
            return self::OTS_AGGREGATOR_CODE;
        }

        // Replicates Participant::isMembershipActive() on raw columns.
        $isActiveMember = $participant->membership_type !== 'none'
            && $participant->membership_end_date
            && $participant->membership_end_date >= now()->toDateString();

        return app(ParticipantCodeService::class)->next($isActiveMember ? '' : 'NM');
    }
};
