<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->text('medical_conditions')->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->enum('jersey_size', ['XS', 'S', 'M', 'L', 'XL', 'XXL'])->nullable();
            $table->enum('membership_type', ['tahunan', 'setengah_tahun', 'mingguan', 'none'])->default('none');
            $table->date('membership_start_date')->nullable();
            $table->date('membership_end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('total_events_participated')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
