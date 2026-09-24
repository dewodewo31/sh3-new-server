<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InventoryCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Networking',
            'IT',
            'Documentation',
            'Audio',
            'Event Equipment',
            'Race Equipment',
            'Office Furniture',
            'Electrical',
            'Other',
        ];

        foreach ($categories as $index => $name) {
            InventoryCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
