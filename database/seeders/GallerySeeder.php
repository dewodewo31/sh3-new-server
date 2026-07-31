<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Gallery;
use App\Models\GalleryAlbum;
use App\Models\User;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin_laman')->first()?->id ?? User::first()?->id;
        $events = Event::all();

        if ($events->isEmpty()) {
            return;
        }

        foreach ($events as $event) {
            $albumCount = 1 + ($event->id % 2);

            for ($i = 1; $i <= $albumCount; $i++) {
                $album = GalleryAlbum::updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'title' => $event->title.' - Album '.$i,
                    ],
                    [
                        'description' => fake()->sentence(),
                    ]
                );

                $imageCount = 3 + (($event->id + $i) % 4);

                for ($j = 1; $j <= $imageCount; $j++) {
                    Gallery::updateOrCreate(
                        [
                            'event_id' => $event->id,
                            'gallery_album_id' => $album->id,
                            'title' => $event->title.' Foto '.$j,
                        ],
                        [
                            'description' => fake()->sentence(),
                            'file_path' => 'galleries/events/'.$event->slug.'/foto-'.$j.'.jpg',
                            'thumbnail_path' => 'galleries/events/'.$event->slug.'/thumb-foto-'.$j.'.jpg',
                            'type' => 'image',
                            'is_featured' => $j === 1,
                            'sort_order' => $j,
                            'created_by' => $admin,
                        ]
                    );
                }
            }
        }
    }
}
