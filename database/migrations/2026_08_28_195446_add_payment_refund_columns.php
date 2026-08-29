<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payment refund lifecycle (audit finding H5 / V12).
     *
     * Adds auditor columns for the refunded state. The `status` column already
     * accepts arbitrary strings, so 'refunded' needs no enum change.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete()->after('confirmed_by');
            $table->timestamp('refunded_at')->nullable()->after('refunded_by');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['refunded_by']);
            $table->dropColumn(['refunded_by', 'refunded_at']);
        });
    }
};
