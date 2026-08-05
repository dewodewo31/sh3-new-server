<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            MembershipPlanSeeder::class,
            CategorySeeder::class,
            ParticipantSeeder::class,
            EventSeeder::class,
            SponsorSeeder::class,
            EventParticipantSeeder::class,
            MerchandiseSeeder::class,
            OrganizationMemberSeeder::class,
            GallerySeeder::class,
        ]);
    }
}
