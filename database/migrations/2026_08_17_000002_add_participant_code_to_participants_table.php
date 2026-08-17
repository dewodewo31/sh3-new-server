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
        Schema::table('participants', function (Blueprint $table) {
            $table->string('participant_code')->nullable()->after('hash_id');
        });

        DB::table('participants')
            ->orderBy('id')
            ->chunkById(500, function ($participants) {
                foreach ($participants as $participant) {
                    DB::table('participants')->where('id', $participant->id)->update([
                        'participant_code' => $this->codeFor($participant),
                    ]);
                }
            });

        Schema::table('participants', function (Blueprint $table) {
            $table->string('participant_code')->nullable(false)->change();
            $table->unique('participant_code');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropUnique(['participant_code']);
            $table->dropColumn('participant_code');
        });

        // hash_id is still present unless 2026_08_14_000100 was rolled back first.
        if (! Schema::hasColumn('participants', 'hash_id')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->string('hash_id')->nullable()->unique()->after('user_id');
            });
        }
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
