<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_external_loans', function (Blueprint $table) {
            $table->id();
            $table->string('external_party');
            $table->string('contact_name')->nullable();
            $table->string('contact_info')->nullable();
            $table->text('items_description');
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'approved', 'borrowed', 'returned', 'cancelled'])->default('approved');
            $table->date('borrow_date')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->enum('condition_before', ['excellent', 'good', 'fair', 'damaged', 'critical'])->nullable();
            $table->enum('condition_after', ['excellent', 'good', 'fair', 'damaged', 'critical'])->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('returned_received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('inventory_external_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_external_loan_id')->constrained('inventory_external_loans')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('from_location');
            $table->string('to_name')->nullable();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('receiver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receiver_name')->nullable();
            $table->enum('condition_at_handover', ['excellent', 'good', 'fair', 'damaged', 'critical']);
            $table->timestamp('handed_over_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_external_handovers');
        Schema::dropIfExists('inventory_external_loans');
    }
};
