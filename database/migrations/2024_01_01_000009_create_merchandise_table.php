<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchandise', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->json('size_options')->nullable();
            $table->integer('stock')->default(0);
            $table->string('image')->nullable();
            $table->enum('status', ['available', 'sold_out', 'discontinued'])->default('available');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('merchandise_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandise_id')->constrained('merchandise')->restrictOnDelete();
            $table->foreignId('participant_id')->constrained()->restrictOnDelete();
            $table->string('customer_name');
            $table->string('customer_contact');
            $table->string('size');
            $table->integer('quantity');
            $table->decimal('total_price', 15, 2);
            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_orders');
        Schema::dropIfExists('merchandise');
    }
};
