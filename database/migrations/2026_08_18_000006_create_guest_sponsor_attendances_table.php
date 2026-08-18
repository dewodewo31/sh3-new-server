<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_sponsor_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_sponsor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->enum('status', ['present', 'absent'])->default('absent');
            $table->enum('check_in_method', ['qr_code', 'manual'])->default('qr_code');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['guest_sponsor_id', 'event_id'], 'gsa_guest_event_unique');
        });

        Schema::create('guest_sponsor_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_sponsor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['check_in', 'check_out']);
            $table->dateTime('scan_time');
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('qr_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['guest_sponsor_id', 'event_id', 'type'], 'gsal_guest_event_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_sponsor_attendance_logs');
        Schema::dropIfExists('guest_sponsor_attendances');
    }
};
