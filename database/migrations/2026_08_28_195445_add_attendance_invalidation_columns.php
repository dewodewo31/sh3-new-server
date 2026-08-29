<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attendance invalidation lifecycle (audit finding H2 / V11 / V12).
     *
     * When an attendance is invalidated (e.g. the event registration payment is
     * rejected/refunded) the original EARN ledger row is never mutated; instead a
     * separate REVERSAL ledger entry is created. These columns record WHO/WHY/WHEN
     * the attendance was invalidated for audit purposes.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_invalid')->default(false)->after('status');
            $table->foreignId('invalidated_by')->nullable()->constrained('users')->nullOnDelete()->after('is_invalid');
            $table->timestamp('invalidated_at')->nullable()->after('invalidated_by');
            $table->text('invalidation_reason')->nullable()->after('invalidated_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['invalidated_by']);
            $table->dropColumn(['is_invalid', 'invalidated_by', 'invalidated_at', 'invalidation_reason']);
        });
    }
};
