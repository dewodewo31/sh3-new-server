<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->enum('source', ['local', 'gdrive'])->default('local')->after('description');
            $table->text('google_drive_url')->nullable()->after('source');
            $table->string('google_drive_file_id')->nullable()->after('google_drive_url');
        });
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropColumn(['google_drive_file_id', 'google_drive_url', 'source']);
        });
    }
};