<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Immutable point ledger (append-only).
 *
 * Every point movement is an INSERT; rows are never UPDATE/DELETE'd.
 *
 *   type       EARN | REDEEM | REVERSAL | ADJUSTMENT
 *   amount     signed integer. EARN>0, REDEEM<0, REVERSAL>0 (returns spent
 *              points), ADJUSTMENT signed (admin/technical correction).
 *
 * Idempotency:
 *   UNIQUE(source_type, source_id) — for EARN rows source_id = attendance_id
 *   so one attendance yields at most one EARN; for REDEEM rows source_id =
 *   merchandise_order_id so an order is never double-deducted; for REVERSAL
 *   rows source_id = the originating transaction/order so a reversal is never
 *   applied twice. NULL source values are exempt (each NULL is distinct), so
 *   ADJUSTMENT rows (which may carry no source) are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['EARN', 'REDEEM', 'REVERSAL', 'ADJUSTMENT']);
            $table->integer('amount');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            // EARN metadata / rate snapshot
            $table->foreignId('membership_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('point_rate')->nullable();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('merchandise_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ref')->nullable();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['participant_id', 'created_at'], 'idx_point_tx_participant_created');
            $table->unique(['source_type', 'source_id'], 'idx_point_tx_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
