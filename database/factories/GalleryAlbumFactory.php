<?php

namespace Database\Factories;

use App\Models\GalleryAlbum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryAlbum>
 */
class GalleryAlbumFactory extends Factory
{
    protected $model = GalleryAlbum::class;

    public function definition(): array
    {
        return [
            'event_id' => null,
            'title' => fake()->sentence(2),
            'description' => fake()->sentence(),
            'cover_image' => null,
        ];
    }
}
