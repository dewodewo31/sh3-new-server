<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Long Run', 'description' => 'Lari jarak jauh rutin', 'distance_km' => 10.00, 'sort_order' => 1],
            ['name' => 'Short Run', 'description' => 'Lari jarak pendek', 'distance_km' => 5.00, 'sort_order' => 2],
            ['name' => 'Major Events', 'description' => 'Event utama SH3', 'distance_km' => 21.10, 'sort_order' => 3],
            ['name' => 'Super Long', 'description' => 'Lari ultra jarak jauh', 'distance_km' => 42.20, 'sort_order' => 4],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'distance_km' => $cat['distance_km'],
                    'sort_order' => $cat['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
