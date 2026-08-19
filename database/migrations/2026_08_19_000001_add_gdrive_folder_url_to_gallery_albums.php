<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->text('gdrive_folder_url')->nullable()->after('cover_image');
        });
    }

    public function down(): void
    {
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->dropColumn('gdrive_folder_url');
        });
    }
};
