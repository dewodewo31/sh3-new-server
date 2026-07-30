<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['check_in', 'check_out']);
            $table->dateTime('scan_time');
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('qr_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'participant_id', 'type']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_participant_id')->constrained()->cascadeOnDelete();
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'left_early'])->default('absent');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('check_in_method', ['qr_code', 'manual', 'self_scan'])->default('qr_code');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('attendance_logs');
    }
};
