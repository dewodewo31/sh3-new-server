<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // attendances: sync-down queries ORDER BY updated_at
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('updated_at', 'idx_attendances_updated_at');
        });

        // events: listing by status + start_date
        Schema::table('events', function (Blueprint $table) {
            $table->index(['status', 'start_date'], 'idx_events_status_start_date');
        });

        // galleries: listing by type + sort
        Schema::table('galleries', function (Blueprint $table) {
            $table->index(['type', 'is_featured', 'sort_order'], 'idx_galleries_type_featured_sort');
        });

        // categories: listing by is_active + sort_order
        Schema::table('categories', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'idx_categories_active_sort');
        });

        // organization_members: listing by is_active + sort_order
        Schema::table('organization_members', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'idx_org_members_active_sort');
        });

        // membership_plans: listing by is_active + sort_order
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order', 'id'], 'idx_membership_plans_active_sort_id');
        });

        // merchandise: listing by status
        Schema::table('merchandise', function (Blueprint $table) {
            $table->index('status', 'idx_merchandise_status');
        });

        // payments: listing by status + created_at
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_payments_status_created');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_updated_at');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_status_start_date');
        });
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropIndex('idx_galleries_type_featured_sort');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_active_sort');
        });
        Schema::table('organization_members', function (Blueprint $table) {
            $table->dropIndex('idx_org_members_active_sort');
        });
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->dropIndex('idx_membership_plans_active_sort_id');
        });
        Schema::table('merchandise', function (Blueprint $table) {
            $table->dropIndex('idx_merchandise_status');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_status_created');
        });
    }
};
