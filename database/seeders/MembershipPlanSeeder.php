<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'key' => 'tahunan',
                'name' => 'Tahunan',
                'description' => 'Membership 12 bulan',
                'price' => 400000,
                'duration' => 12,
                'duration_unit' => 'months',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'setengah_tahun',
                'name' => 'Setengah Tahun',
                'description' => 'Membership 6 bulan',
                'price' => 250000,
                'duration' => 6,
                'duration_unit' => 'months',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'mingguan',
                'name' => 'Mingguan',
                'description' => 'Membership 7 hari',
                'price' => 10000,
                'duration' => 7,
                'duration_unit' => 'days',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(['key' => $plan['key']], $plan);
        }
    }
}
