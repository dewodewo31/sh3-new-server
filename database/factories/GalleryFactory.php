<?php

namespace Database\Factories;

use App\Models\Gallery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    protected $model = Gallery::class;

    public function definition(): array
    {
        return [
            'event_id' => null,
            'gallery_album_id' => null,
            'title' => fake()->sentence(2),
            'description' => fake()->sentence(),
            'file_path' => 'gallery/'.fake()->uuid().'.jpg',
            'thumbnail_path' => null,
            'type' => 'image',
            'is_featured' => false,
            'sort_order' => fake()->numberBetween(0, 10),
            'created_by' => null,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
