<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MembershipPlan becomes the single source of pricing rules.
     * - pricing config: base_event_price, discount_percentage, reference_event_count
     * - duration_unit gains 'years' so the annual plan is expressible
     * - membership_histories snapshots the computed values so a past membership
     *   never changes when the active plan is later edited (backward compatible).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE membership_plans MODIFY duration_unit ENUM('days','months','years') NOT NULL DEFAULT 'months'");

        Schema::table('membership_plans', function (Blueprint $table) {
            $table->unsignedInteger('base_event_price')->default(25000)->after('description');
            $table->unsignedInteger('discount_percentage')->default(0)->after('base_event_price');
            $table->unsignedInteger('reference_event_count')->default(0)->after('discount_percentage');
        });

        Schema::table('membership_histories', function (Blueprint $table) {
            $table->date('normal_end_date')->nullable()->after('end_date');
            $table->unsignedInteger('eligible_event_count')->nullable()->after('normal_end_date');
            $table->unsignedInteger('base_event_price')->nullable()->after('eligible_event_count');
            $table->unsignedInteger('discount_percentage')->nullable()->after('base_event_price');
            $table->unsignedInteger('effective_event_price')->nullable()->after('discount_percentage');
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE membership_plans MODIFY duration_unit ENUM('days','months') NOT NULL DEFAULT 'months'");

        Schema::table('membership_plans', function (Blueprint $table) {
            $table->dropColumn(['base_event_price', 'discount_percentage', 'reference_event_count']);
        });

        Schema::table('membership_histories', function (Blueprint $table) {
            $table->dropColumn(['normal_end_date', 'eligible_event_count', 'base_event_price', 'discount_percentage', 'effective_event_price']);
        });
    }
};
