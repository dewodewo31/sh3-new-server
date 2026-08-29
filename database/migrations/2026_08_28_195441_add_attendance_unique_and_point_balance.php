<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 (BLOCKER) + Phase 3 pre-req.
 *
 * 1. Enforce a UNIQUE event_participant_id on `attendances` so a single event
 *    registration can only ever yield ONE attendance row. This closes the
 *    read-then-write race in AttendanceService::checkIn() and syncUpOffline():
 *    two concurrent check-ins for the same registration can no longer both
 *    create an attendance (and later both grant points).
 *
 *    Backfill: if any historical duplicate attendance rows exist for the same
 *    registration, keep only the earliest (lowest id) and delete the rest — a
 *    registration is logically 1:1 with its attendance.
 *
 * 2. Add participants.point_balance as a denormalized balance cache that is
 *    recomputable from the (append-only) point_transactions ledger.
 */
return new class extends Migration
{
    public function up(): void
 {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement('DELETE a FROM attendances a
                LEFT JOIN (
                    SELECT MIN(id) AS id FROM attendances GROUP BY event_participant_id
                ) keep ON keep.id = a.id
                WHERE keep.id IS NULL');
        } else {
            // Generic fallback for non-MySQL drivers (sqlite/pgsql used in some test harnesses).
            $rows = DB::table('attendances')
                ->select('event_participant_id')
                ->groupBy('event_participant_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($rows as $row) {
                $keep = DB::table('attendances')
                    ->where('event_participant_id', $row->event_participant_id)
                    ->orderBy('id')
                    ->value('id');

                DB::table('attendances')
                    ->where('event_participant_id', $row->event_participant_id)
                    ->where('id', '<>', $keep)
                    ->delete();
            }
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique('event_participant_id', 'idx_attendances_event_participant_unique');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->integer('point_balance')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('point_balance');
        });

        Schema::table('attendances', function (Blueprint $table) {
            // MySQL cannot drop a unique index on a column that a FK still
            // references, so drop that FK edge first and re-create it after.
            $table->dropForeign(['event_participant_id']);
            $table->dropUnique('idx_attendances_event_participant_unique');
            $table->foreign('event_participant_id')->references('id')->on('event_participants')->cascadeOnDelete();
        });
    }
};
