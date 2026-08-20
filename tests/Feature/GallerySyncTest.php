<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GallerySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_albums_have_gdrive_sync_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('gallery_albums', 'last_synced_at'));
        $this->assertTrue(Schema::hasColumn('gallery_albums', 'gdrive_sync_error'));
    }

    public function test_google_drive_api_key_config_is_readable(): void
    {
        config(['services.google_drive.api_key' => 'test-key-123']);

        $this->assertSame('test-key-123', config('services.google_drive.api_key'));
    }

    public function test_galleries_have_composite_gdrive_index(): void
    {
        $this->assertTrue(
            Schema::hasIndex('galleries', ['gallery_album_id', 'google_drive_file_id'])
        );
    }
}
