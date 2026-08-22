<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['name' => 'Lari Pagi', 'sort_order' => 1],
            ['name' => 'City Run', 'sort_order' => 2],
            ['name' => 'Major Event', 'sort_order' => 3],
        ];

        foreach ($activities as $activity) {
            Activity::firstOrCreate(
                ['name' => $activity['name']],
                ['sort_order' => $activity['sort_order'], 'is_active' => true]
            );
        }
    }
}
