<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookkeepings', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'paid', 'cancelled'])
                ->default('paid');
            $table->foreignId('financial_account_id')->nullable()
                ->constrained('financial_accounts')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()
                ->constrained('activities')->nullOnDelete();
            $table->string('payee')->nullable();
            $table->date('due_date')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedInteger('reference_id')->nullable();
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->index('transaction_date');
            $table->index(['type', 'status']);
            $table->index('event_id');
            $table->index('activity_id');
            $table->index('financial_account_id');
            $table->index('status');
        });

        // Idempotent backfill: existing rows have no status yet.
        DB::statement("UPDATE bookkeepings SET status='paid' WHERE status IS NULL");
    }

    public function down(): void
    {
        Schema::table('bookkeepings', function (Blueprint $table) {
            $table->dropForeign(['financial_account_id']);
            $table->dropForeign(['activity_id']);
            $table->dropForeign(['approved_by']);

            $table->dropIndex(['transaction_date']);
            $table->dropIndex(['type', 'status']);
            $table->dropIndex(['event_id']);
            $table->dropIndex(['activity_id']);
            $table->dropIndex(['financial_account_id']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'status',
                'financial_account_id',
                'activity_id',
                'payee',
                'due_date',
                'reference_type',
                'reference_id',
                'approved_by',
                'approved_at',
            ]);
        });
    }
};
