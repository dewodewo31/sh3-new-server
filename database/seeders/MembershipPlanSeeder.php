<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        // `price` is the FINAL PACKAGE PRICE paid by participants and is stored
        // as-is (admin-defined). base_event_price / discount_percentage /
        // reference_event_count remain as informational pricing config.
        // base_event_price = Rp25.000, discounts 10%/5%/0%, reference event counts 53/26/1.
        $plans = [
            [
                'key' => 'tahunan',
                'name' => 'Tahunan',
                'description' => 'Membership 1 tahun',
                'price' => 1192500,
                'base_event_price' => 25000,
                'discount_percentage' => 10,
                'reference_event_count' => 53,
                'duration' => 1,
                'duration_unit' => 'years',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'setengah_tahun',
                'name' => 'Setengah Tahun',
                'description' => 'Membership 6 bulan',
                'price' => 617500,
                'base_event_price' => 25000,
                'discount_percentage' => 5,
                'reference_event_count' => 26,
                'duration' => 6,
                'duration_unit' => 'months',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'mingguan',
                'name' => 'Mingguan',
                'description' => 'Membership 7 hari',
                'price' => 25000,
                'base_event_price' => 25000,
                'discount_percentage' => 0,
                'reference_event_count' => 1,
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
