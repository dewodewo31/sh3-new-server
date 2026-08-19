<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\GalleryAlbum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryApiTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Long Run',
            'slug' => 'long-run',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    private function createEvent(): Event
    {
        return Event::create([
            'category_id' => $this->category->id,
            'title' => 'SH3 Anniversary Run',
            'slug' => 'sh3-anniversary-run-'.uniqid(),
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(7)->addHours(6),
            'registration_start_date' => now()->subDay(),
            'registration_end_date' => now()->addDays(5),
            'status' => 'publish',
        ]);
    }

    private function createGallery(array $overrides = []): Gallery
    {
        return Gallery::create(array_merge([
            'title' => 'Finish Line Photo',
            'type' => 'image',
            'source' => 'local',
            'is_featured' => false,
            'sort_order' => 0,
        ], $overrides));
    }

    public function test_public_gallery_index_returns_only_featured_images(): void
    {
        $featured = $this->createGallery(['title' => 'Featured Image', 'is_featured' => true]);
        $this->createGallery(['title' => 'Hidden Image', 'is_featured' => false]);
        $this->createGallery(['title' => 'Featured Video', 'type' => 'video', 'is_featured' => true]);

        $response = $this->getJson('/api/v1/galleries');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $featured->id)
            ->assertJsonPath('data.0.title', 'Featured Image');
    }

    public function test_public_gallery_index_returns_empty_when_no_featured_image(): void
    {
        $this->createGallery(['title' => 'Hidden Image', 'is_featured' => false]);

        $this->getJson('/api/v1/galleries')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_event_detail_galleries_only_include_featured_images(): void
    {
        $event = $this->createEvent();
        $this->createGallery(['title' => 'Featured Image', 'is_featured' => true, 'event_id' => $event->id, 'file_path' => 'galleries/featured.jpg']);
        $this->createGallery(['title' => 'Hidden Image', 'is_featured' => false, 'event_id' => $event->id]);
        $this->createGallery(['title' => 'Featured Video', 'type' => 'video', 'is_featured' => true, 'event_id' => $event->id]);

        $response = $this->getJson("/api/v1/events/{$event->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.galleries')
            ->assertJsonPath('data.galleries.0', Storage::disk('public')->url('galleries/featured.jpg'));
    }

    public function test_event_detail_galleries_empty_when_no_featured_image(): void
    {
        $event = $this->createEvent();
        $this->createGallery(['title' => 'Hidden Image', 'is_featured' => false, 'event_id' => $event->id]);

        $this->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data.galleries');
    }

    public function test_gdrive_gallery_uses_thumbnail_url_when_file_id_present(): void
    {
        $this->createGallery([
            'title' => 'Drive Photo',
            'source' => 'gdrive',
            'is_featured' => true,
            'google_drive_url' => 'https://drive.google.com/file/d/abc123def/view?usp=sharing',
            'google_drive_file_id' => 'abc123def',
        ]);

        $this->getJson('/api/v1/galleries')
            ->assertOk()
            ->assertJsonPath('data.0.url', 'https://drive.google.com/thumbnail?id=abc123def&sz=w800')
            ->assertJsonPath('data.0.thumb', 'https://drive.google.com/thumbnail?id=abc123def&sz=w800');
    }

    public function test_gdrive_gallery_falls_back_to_raw_url_without_file_id(): void
    {
        $rawUrl = 'https://drive.google.com/file/d/xyz789/view';

        $this->createGallery([
            'title' => 'Drive Photo No Id',
            'source' => 'gdrive',
            'is_featured' => true,
            'google_drive_url' => $rawUrl,
            'google_drive_file_id' => null,
        ]);

        $this->getJson('/api/v1/galleries')
            ->assertOk()
            ->assertJsonPath('data.0.url', $rawUrl);
    }

    public function test_event_detail_gdrive_gallery_uses_thumbnail_url(): void
    {
        $event = $this->createEvent();
        $this->createGallery([
            'title' => 'Drive Photo',
            'source' => 'gdrive',
            'is_featured' => true,
            'event_id' => $event->id,
            'google_drive_url' => 'https://drive.google.com/file/d/thumb456/view',
            'google_drive_file_id' => 'thumb456',
        ]);

        $this->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.galleries.0', 'https://drive.google.com/thumbnail?id=thumb456&sz=w800');
    }

    public function test_public_gallery_albums_endpoint_returns_folder_url_and_count(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'SH3 Anniversary',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUv',
        ]);

        $this->createGallery(['title' => 'Photo 1', 'gallery_album_id' => $album->id]);
        $this->createGallery(['title' => 'Photo 2', 'gallery_album_id' => $album->id]);

        $this->getJson('/api/v1/gallery-albums')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'SH3 Anniversary')
            ->assertJsonPath('data.0.gdrive_folder_url', 'https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUv')
            ->assertJsonPath('data.0.galleries_count', 2);
    }

    public function test_public_gallery_albums_returns_null_folder_url_without_folder(): void
    {
        GalleryAlbum::create(['title' => 'No Drive Album']);

        $this->getJson('/api/v1/gallery-albums')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.gdrive_folder_url', null);
    }
}
