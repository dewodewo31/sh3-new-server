<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\GalleryAlbum;
use App\Services\GalleryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
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

    public function test_sync_failure_403_preserves_snapshot(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'A',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FA',
        ]);
        Gallery::create([
            'title' => 'Keep',
            'gallery_album_id' => $album->id,
            'source' => 'gdrive',
            'google_drive_file_id' => 'keep1',
        ]);

        Http::fake(['www.googleapis.com/*' => Http::response([], 403)]);

        app(GalleryService::class)->syncAllDriveAlbums();

        $album->refresh();
        $this->assertNotNull($album->gdrive_sync_error);
        $this->assertStringContainsString('tidak dapat diakses', $album->gdrive_sync_error);
        $this->assertDatabaseCount('galleries', 1);
        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'keep1',
        ]);
    }

    public function test_sync_failure_429_preserves_snapshot(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'A',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FA',
        ]);
        Gallery::create([
            'title' => 'Keep',
            'gallery_album_id' => $album->id,
            'source' => 'gdrive',
            'google_drive_file_id' => 'keep1',
        ]);

        Http::fake(['www.googleapis.com/*' => Http::response([], 429)]);

        app(GalleryService::class)->syncAllDriveAlbums();

        $album->refresh();
        $this->assertNotNull($album->gdrive_sync_error);
        $this->assertStringContainsString('rate limit', $album->gdrive_sync_error);
        $this->assertDatabaseCount('galleries', 1);
    }

    public function test_sync_failure_5xx_preserves_snapshot(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'A',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FA',
        ]);
        Gallery::create([
            'title' => 'Keep',
            'gallery_album_id' => $album->id,
            'source' => 'gdrive',
            'google_drive_file_id' => 'keep1',
        ]);

        Http::fake(['www.googleapis.com/*' => Http::response([], 503)]);

        app(GalleryService::class)->syncAllDriveAlbums();

        $album->refresh();
        $this->assertNotNull($album->gdrive_sync_error);
        $this->assertStringContainsString('HTTP 503', $album->gdrive_sync_error);
        $this->assertDatabaseCount('galleries', 1);
    }

    public function test_sync_failure_network_preserves_snapshot(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'A',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FA',
        ]);
        Gallery::create([
            'title' => 'Keep',
            'gallery_album_id' => $album->id,
            'source' => 'gdrive',
            'google_drive_file_id' => 'keep1',
        ]);

        Http::fake(['www.googleapis.com/*' => fn ($request) => throw new ConnectionException('timeout')]);

        app(GalleryService::class)->syncAllDriveAlbums();

        $album->refresh();
        $this->assertNotNull($album->gdrive_sync_error);
        $this->assertStringContainsString('tidak dapat dijangkau', $album->gdrive_sync_error);
        $this->assertDatabaseCount('galleries', 1);
    }

    public function test_sync_partial_page_failure_writes_nothing(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'A',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FA',
        ]);
        Gallery::create([
            'title' => 'Keep',
            'gallery_album_id' => $album->id,
            'source' => 'gdrive',
            'google_drive_file_id' => 'keep1',
        ]);

        Http::fake(function ($request) {
            if (($request['pageToken'] ?? null) === 'tok2') {
                throw new ConnectionException('timeout');
            }

            return Http::response([
                'files' => [['id' => 'img1', 'name' => 'foto.jpg', 'mimeType' => 'image/jpeg']],
                'nextPageToken' => 'tok2',
            ], 200);
        });

        app(GalleryService::class)->syncAllDriveAlbums();

        $album->refresh();
        $this->assertNotNull($album->gdrive_sync_error);
        $this->assertDatabaseMissing('galleries', ['google_drive_file_id' => 'img1']);
        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'keep1',
        ]);
        $this->assertDatabaseCount('galleries', 1);
    }

    public function test_sync_multi_album_failure_does_not_stop_other_albums(): void
    {
        $albumA = GalleryAlbum::create([
            'title' => 'A',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FA',
        ]);
        $albumB = GalleryAlbum::create([
            'title' => 'B',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FB',
        ]);

        Http::fake(function ($request) {
            $q = $request['q'] ?? '';

            if (str_contains($q, "'FA' in parents")) {
                return Http::response([], 403);
            }

            return Http::response([
                'files' => [['id' => 'fb1', 'name' => 'b.jpg', 'mimeType' => 'image/jpeg']],
                'nextPageToken' => null,
            ], 200);
        });

        $results = app(GalleryService::class)->syncAllDriveAlbums();

        $byAlbum = collect($results)->keyBy('album_id');
        $this->assertSame('error', $byAlbum[$albumA->id]['status']);
        $this->assertSame('synced', $byAlbum[$albumB->id]['status']);

        $albumA->refresh();
        $this->assertNotNull($albumA->gdrive_sync_error);
        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $albumB->id,
            'google_drive_file_id' => 'fb1',
        ]);
    }

    public function test_sync_stale_delete_is_scoped_per_album(): void
    {
        $albumA = GalleryAlbum::create([
            'title' => 'A',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FA',
        ]);
        $albumB = GalleryAlbum::create([
            'title' => 'B',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FB',
        ]);

        $emptyFolderA = false;

        Http::fake(function ($request) use (&$emptyFolderA) {
            $q = $request['q'] ?? '';

            if (str_contains($q, "'FA' in parents")) {
                return $emptyFolderA
                    ? Http::response(['files' => [], 'nextPageToken' => null], 200)
                    : Http::response([
                        'files' => [['id' => 'fa1', 'name' => 'a.jpg', 'mimeType' => 'image/jpeg']],
                        'nextPageToken' => null,
                    ], 200);
            }

            return Http::response([
                'files' => [['id' => 'fb1', 'name' => 'b.jpg', 'mimeType' => 'image/jpeg']],
                'nextPageToken' => null,
            ], 200);
        });

        $service = app(GalleryService::class);
        $service->syncAllDriveAlbums();

        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $albumA->id,
            'google_drive_file_id' => 'fa1',
        ]);
        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $albumB->id,
            'google_drive_file_id' => 'fb1',
        ]);

        $emptyFolderA = true;
        $service->syncAllDriveAlbums();

        $this->assertDatabaseMissing('galleries', ['gallery_album_id' => $albumA->id]);
        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $albumB->id,
            'google_drive_file_id' => 'fb1',
        ]);
    }

    public function test_sync_failure_error_is_sanitized(): void
    {
        config(['services.google_drive.api_key' => 'super-secret-key']);

        $album = GalleryAlbum::create([
            'title' => 'A',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FA',
        ]);

        Http::fake(['www.googleapis.com/*' => Http::response([], 403)]);

        app(GalleryService::class)->syncAllDriveAlbums();

        $album->refresh();
        $this->assertNotNull($album->gdrive_sync_error);
        $this->assertStringNotContainsString('super-secret-key', $album->gdrive_sync_error);
        $this->assertStringNotContainsString('drive.google.com', $album->gdrive_sync_error);
    }

    private function fakeFolderFiles(array $files)
    {
        return Http::response(['files' => $files, 'nextPageToken' => null], 200);
    }
}
