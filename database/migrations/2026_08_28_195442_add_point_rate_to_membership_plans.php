<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Flat Point rate per membership plan (tier).
 *
 * point_per_event_checkin = how many flat points one valid event
 * attendance/check-in earns while this plan is the participant's ACTIVE
 * membership at check-in time. Null/0 = member earns 0 points (or use the
 * separate non-member default in PointRateService).
 *
 * The rate is SNAPSHOTTED into the point_transactions ledger row when an EARN
 * is created, so later edits to this column are never retroactive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->integer('point_per_event_checkin')->default(0)->after('reference_event_count');
        });
    }

    public function down(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->dropColumn('point_per_event_checkin');
        });
    }
};
