<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->timestamp('last_synced_at')->nullable()->after('gdrive_folder_url');
            $table->text('gdrive_sync_error')->nullable()->after('last_synced_at');
        });

        Schema::table('galleries', function (Blueprint $table) {
            $table->index(['gallery_album_id', 'google_drive_file_id']);
        });
    }

    public function down(): void
    {
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->dropColumn(['last_synced_at', 'gdrive_sync_error']);
        });

        Schema::table('galleries', function (Blueprint $table) {
            $table->dropIndex(['gallery_album_id', 'google_drive_file_id']);
        });
    }
};
