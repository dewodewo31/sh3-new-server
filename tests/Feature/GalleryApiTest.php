<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Gallery;
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
}
