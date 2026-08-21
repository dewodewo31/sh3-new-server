<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Services\GalleryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

    public function test_sync_creates_image_and_video_from_folder(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Anniversary',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FOLDER',
        ]);

        Http::fake([
            'www.googleapis.com/*' => $this->fakeFolderFiles([
                ['id' => 'img1', 'name' => 'foto.jpg', 'mimeType' => 'image/jpeg'],
                ['id' => 'vid1', 'name' => 'klip.mp4', 'mimeType' => 'video/mp4'],
            ]),
        ]);

        $results = app(GalleryService::class)->syncAllDriveAlbums();

        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'img1',
            'type' => 'image',
            'source' => 'gdrive',
        ]);
        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'vid1',
            'type' => 'video',
            'source' => 'gdrive',
        ]);

        $album->refresh();
        $this->assertNotNull($album->last_synced_at);
        $this->assertNull($album->gdrive_sync_error);

        $this->assertSame('synced', $results[0]['status']);
        $this->assertSame(2, $results[0]['count']);
    }

    public function test_sync_skips_unsupported_mime_and_folders(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Anniversary',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FOLDER',
        ]);

        Http::fake([
            'www.googleapis.com/*' => $this->fakeFolderFiles([
                ['id' => 'doc1', 'name' => 'x.pdf', 'mimeType' => 'application/pdf'],
                ['id' => 'sub', 'name' => 'sub', 'mimeType' => 'application/vnd.google-apps.folder'],
            ]),
        ]);

        app(GalleryService::class)->syncAllDriveAlbums();

        $this->assertDatabaseMissing('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'doc1',
        ]);
        $this->assertDatabaseMissing('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'sub',
        ]);
    }

    public function test_sync_empty_folder_is_safe(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Empty',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FOLDER',
        ]);

        Http::fake([
            'www.googleapis.com/*' => $this->fakeFolderFiles([]),
        ]);

        $results = app(GalleryService::class)->syncAllDriveAlbums();

        $this->assertDatabaseMissing('galleries', ['gallery_album_id' => $album->id]);
        $this->assertSame('synced', $results[0]['status']);
        $this->assertSame(0, $results[0]['count']);

        $album->refresh();
        $this->assertNotNull($album->last_synced_at);
    }

    public function test_sync_is_idempotent_on_resync(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Anniversary',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FOLDER',
        ]);

        $files = [
            ['id' => 'img1', 'name' => 'foto.jpg', 'mimeType' => 'image/jpeg'],
            ['id' => 'vid1', 'name' => 'klip.mp4', 'mimeType' => 'video/mp4'],
        ];

        Http::fake([
            'www.googleapis.com/*' => $this->fakeFolderFiles($files),
        ]);

        $service = app(GalleryService::class);
        $service->syncAllDriveAlbums();
        $service->syncAllDriveAlbums();

        $this->assertDatabaseCount('galleries', 2);
        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'img1',
        ]);
        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'vid1',
        ]);
    }

    public function test_sync_removes_deleted_files_on_full_success(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Anniversary',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FOLDER',
        ]);

        $files = [
            ['id' => 'img1', 'name' => 'foto.jpg', 'mimeType' => 'image/jpeg'],
            ['id' => 'vid1', 'name' => 'klip.mp4', 'mimeType' => 'video/mp4'],
        ];

        Http::fakeSequence('www.googleapis.com/*')
            ->push(['files' => $files, 'nextPageToken' => null], 200)
            ->push(['files' => [
                ['id' => 'img1', 'name' => 'foto.jpg', 'mimeType' => 'image/jpeg'],
            ], 'nextPageToken' => null], 200);

        $service = app(GalleryService::class);
        $service->syncAllDriveAlbums();
        $service->syncAllDriveAlbums();

        $this->assertDatabaseMissing('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'vid1',
        ]);
        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'img1',
        ]);
    }

    public function test_sync_skips_album_without_folder_url(): void
    {
        GalleryAlbum::create(['title' => 'No Drive']);

        app(GalleryService::class)->syncAllDriveAlbums();

        Http::assertNothingSent();
    }

    public function test_sync_skips_when_lock_is_held(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Anniversary',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FOLDER',
        ]);

        $lock = Cache::lock('gallery:sync:'.$album->id, 300);
        $this->assertTrue($lock->get());

        $results = app(GalleryService::class)->syncAllDriveAlbums();

        $this->assertSame('skipped', $results[0]['status']);
        Http::assertNothingSent();
        $this->assertDatabaseCount('galleries', 0);

        $lock->release();

        Http::fake([
            'www.googleapis.com/*' => $this->fakeFolderFiles([
                ['id' => 'img1', 'name' => 'foto.jpg', 'mimeType' => 'image/jpeg'],
            ]),
        ]);

        $results = app(GalleryService::class)->syncAllDriveAlbums();

        $this->assertSame('synced', $results[0]['status']);
        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'img1',
        ]);
    }

    private function fakeFolderFiles(array $files)
    {
        return Http::response(['files' => $files, 'nextPageToken' => null], 200);
    }
}
