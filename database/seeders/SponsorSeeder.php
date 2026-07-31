<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Database\Seeder;

class SponsorSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'sponsor')->first()?->id ?? User::first()?->id;

        $sponsors = [
            ['name' => 'Nike Indonesia', 'tier' => 'platinum', 'website' => 'https://www.nike.com/id', 'sponsorship_value' => 500_000_000, 'sort_order' => 1],
            ['name' => 'Bank Central Asia', 'tier' => 'platinum', 'website' => 'https://www.bca.co.id', 'sponsorship_value' => 400_000_000, 'sort_order' => 2],
            ['name' => 'Adidas Running', 'tier' => 'gold', 'website' => 'https://www.adidas.co.id', 'sponsorship_value' => 250_000_000, 'sort_order' => 3],
            ['name' => 'Pocari Sweat', 'tier' => 'gold', 'website' => 'https://www.pocarisweat.id', 'sponsorship_value' => 200_000_000, 'sort_order' => 4],
            ['name' => 'Garmin Indonesia', 'tier' => 'silver', 'website' => 'https://www.garmin.co.id', 'sponsorship_value' => 100_000_000, 'sort_order' => 5],
            ['name' => 'Mizuno Indonesia', 'tier' => 'silver', 'website' => 'https://www.mizuno.id', 'sponsorship_value' => 75_000_000, 'sort_order' => 6],
            ['name' => 'Indomie', 'tier' => 'bronze', 'website' => 'https://www.indomie.com', 'sponsorship_value' => 50_000_000, 'sort_order' => 7],
            ['name' => 'Aqua', 'tier' => 'bronze', 'website' => 'https://www.aqua.com', 'sponsorship_value' => 40_000_000, 'sort_order' => 8],
            ['name' => 'Detik Sport', 'tier' => 'media_partner', 'website' => 'https://sport.detik.com', 'sponsorship_value' => 20_000_000, 'sort_order' => 9],
            ['name' => 'RRI Sport', 'tier' => 'media_partner', 'website' => 'https://www.rri.co.id', 'sponsorship_value' => 15_000_000, 'sort_order' => 10],
        ];

        foreach ($sponsors as $data) {
            Sponsor::updateOrCreate(
                ['name' => $data['name']],
                [
                    'description' => fake()->sentence(),
                    'website' => $data['website'],
                    'contact_person' => fake()->name(),
                    'contact_email' => strtolower(str_replace(' ', '', $data['name'])).'@example.com',
                    'contact_phone' => '08'.fake()->numerify('##########'),
                    'tier' => $data['tier'],
                    'year' => now()->year,
                    'sponsorship_value' => $data['sponsorship_value'],
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                    'created_by' => $admin,
                ]
            );
        }

        $this->attachSponsorsToEvents();
    }

    private function attachSponsorsToEvents(): void
    {
        $events = Event::whereIn('status', ['publish', 'ongoing', 'completed'])->get();
        $sponsors = Sponsor::pluck('id')->toArray();

        foreach ($events as $event) {
            $count = 3 + ($event->id % 3);
            $selected = array_slice($sponsors, 0, $count);

            $pivots = [];
            foreach ($selected as $sponsorId) {
                $pivots[$sponsorId] = [
                    'package' => ['Main Sponsor', 'Co-Sponsor', 'Official Apparel', 'Official Drink', 'Media'][$sponsorId % 5],
                    'value' => 10_000_000 + ($event->id * 5_000_000) + ($sponsorId * 1_000_000),
                    'status' => 'approved',
                ];
            }

            $event->sponsors()->syncWithoutDetaching($pivots);
        }
    }
}
