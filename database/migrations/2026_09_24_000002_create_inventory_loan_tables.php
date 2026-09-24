<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->nullableMorphs('borrower');
            $table->string('borrower_name');
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'approved', 'borrowed', 'returned', 'cancelled'])->default('approved');
            $table->date('borrow_date')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->enum('condition_before', ['excellent', 'good', 'fair', 'damaged', 'critical'])->nullable();
            $table->enum('condition_after', ['excellent', 'good', 'fair', 'damaged', 'critical'])->nullable();
            $table->unsignedBigInteger('agreement_document_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('returned_received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['inventory_item_id', 'status']);
        });

        Schema::create('inventory_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_loan_id')->constrained('inventory_loans')->cascadeOnDelete();
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

        Schema::create('inventory_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('inventory_loan_id')->nullable()->constrained('inventory_loans')->nullOnDelete();
            $table->enum('type', ['loan_agreement', 'handover_receipt', 'borrower_id', 'purchase_invoice', 'warranty', 'maintenance', 'return_receipt', 'other']);
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_documents');
        Schema::dropIfExists('inventory_handovers');
        Schema::dropIfExists('inventory_loans');
    }
};
