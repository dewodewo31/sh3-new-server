<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Balance integrity (audit Phase 10 / V2).
     *
     * Enforces `participants.point_balance >= 0` at the database level. This is a
     * defensive backstop; application-level guards in PointService remain the
     * primary enforcement. Safe on MySQL 8 (CHECK constraints supported).
     *
     * Wrapped so a re-run or an unsupported engine fails soft rather than
     * breaking the whole migration batch.
     */
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE participants DROP CHECK IF EXISTS chk_participants_point_balance_nonneg');
        } catch (\Throwable $e) {
            // MySQL < 8.0.16 has no DROP CHECK IF EXISTS; ignore.
        }

        DB::statement('ALTER TABLE participants ADD CONSTRAINT chk_participants_point_balance_nonneg CHECK (point_balance >= 0)');
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE participants DROP CHECK IF EXISTS chk_participants_point_balance_nonneg');
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
