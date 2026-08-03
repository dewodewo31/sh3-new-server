<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('membership_type', 50)->default('none')->change();
        });

        Schema::table('membership_histories', function (Blueprint $table) {
            $table->string('membership_type', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->enum('membership_type', ['tahunan', 'setengah_tahun', 'mingguan', 'none'])->default('none')->change();
        });

        Schema::table('membership_histories', function (Blueprint $table) {
            $table->enum('membership_type', ['tahunan', 'setengah_tahun', 'mingguan'])->change();
        });
    }
};
