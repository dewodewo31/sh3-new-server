<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->enum('registration_type', ['free', 'paid', 'membership'])->default('free');
            $table->decimal('amount', 15, 2)->nullable();
            $table->enum('payment_status', ['pending', 'confirmed', 'rejected', 'refunded'])->default('pending');
            $table->boolean('is_attended')->default(false);
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->string('qr_code')->nullable();
            $table->boolean('is_membership_free')->default(false);
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }
};
