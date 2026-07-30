<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->enum('payment_type', ['event_registration', 'merchandise', 'membership']);
            $table->morphs('paymentable');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['transfer', 'cash', 'qris'])->default('transfer');
            $table->string('payment_proof')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'rejected', 'refunded'])->default('pending');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
