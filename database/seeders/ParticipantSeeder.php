<?php

namespace Database\Seeders;

use App\Models\MembershipHistory;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ParticipantSeeder extends Seeder
{
    public function run(): void
    {
        $memberships = [
            'none' => null,
            'mingguan' => [10_000, 7],
            'setengah_tahun' => [250_000, 182],
            'tahunan' => [400_000, 365],
        ];

        $participants = [
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@example.com', 'phone' => '081234567801', 'gender' => 'male', 'membership_type' => 'tahunan', 'jersey_size' => 'L'],
            ['name' => 'Siti Rahayu', 'email' => 'siti.rahayu@example.com', 'phone' => '081234567802', 'gender' => 'female', 'membership_type' => 'tahunan', 'jersey_size' => 'M'],
            ['name' => 'Agus Wijaya', 'email' => 'agus.wijaya@example.com', 'phone' => '081234567803', 'gender' => 'male', 'membership_type' => 'setengah_tahun', 'jersey_size' => 'XL'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi.lestari@example.com', 'phone' => '081234567804', 'gender' => 'female', 'membership_type' => 'mingguan', 'jersey_size' => 'S'],
            ['name' => 'Rizky Pratama', 'email' => 'rizky.pratama@example.com', 'phone' => '081234567805', 'gender' => 'male', 'membership_type' => 'none', 'jersey_size' => 'M'],
            ['name' => 'Nur Aini', 'email' => 'nur.aini@example.com', 'phone' => '081234567806', 'gender' => 'female', 'membership_type' => 'tahunan', 'jersey_size' => 'L'],
            ['name' => 'Andi Firmansyah', 'email' => 'andi.firmansyah@example.com', 'phone' => '081234567807', 'gender' => 'male', 'membership_type' => 'setengah_tahun', 'jersey_size' => 'XXL'],
            ['name' => 'Maya Anggraini', 'email' => 'maya.anggraini@example.com', 'phone' => '081234567808', 'gender' => 'female', 'membership_type' => 'mingguan', 'jersey_size' => 'XS'],
            ['name' => 'Fajar Hidayat', 'email' => 'fajar.hidayat@example.com', 'phone' => '081234567809', 'gender' => 'male', 'membership_type' => 'none', 'jersey_size' => 'L'],
            ['name' => 'Rina Marlina', 'email' => 'rina.marlina@example.com', 'phone' => '081234567810', 'gender' => 'female', 'membership_type' => 'tahunan', 'jersey_size' => 'M'],
            ['name' => 'Dedi Kurniawan', 'email' => 'dedi.kurniawan@example.com', 'phone' => '081234567811', 'gender' => 'male', 'membership_type' => 'setengah_tahun', 'jersey_size' => 'L'],
            ['name' => 'Putri Handayani', 'email' => 'putri.handayani@example.com', 'phone' => '081234567812', 'gender' => 'female', 'membership_type' => 'mingguan', 'jersey_size' => 'M'],
            ['name' => 'Hendra Gunawan', 'email' => 'hendra.gunawan@example.com', 'phone' => '081234567813', 'gender' => 'male', 'membership_type' => 'none', 'jersey_size' => 'XL'],
            ['name' => 'Lina Sari', 'email' => 'lina.sari@example.com', 'phone' => '081234567814', 'gender' => 'female', 'membership_type' => 'tahunan', 'jersey_size' => 'S'],
            ['name' => 'Yoga Saputra', 'email' => 'yoga.saputra@example.com', 'phone' => '081234567815', 'gender' => 'male', 'membership_type' => 'setengah_tahun', 'jersey_size' => 'L'],
            ['name' => 'Ratna Dewi', 'email' => 'ratna.dewi@example.com', 'phone' => '081234567816', 'gender' => 'female', 'membership_type' => 'tahunan', 'jersey_size' => 'M'],
            ['name' => 'Wawan Setiawan', 'email' => 'wawan.setiawan@example.com', 'phone' => '081234567817', 'gender' => 'male', 'membership_type' => 'none', 'jersey_size' => 'L'],
            ['name' => 'Indah Permata', 'email' => 'indah.permata@example.com', 'phone' => '081234567818', 'gender' => 'female', 'membership_type' => 'mingguan', 'jersey_size' => 'M'],
            ['name' => 'Bayu Nugroho', 'email' => 'bayu.nugroho@example.com', 'phone' => '081234567819', 'gender' => 'male', 'membership_type' => 'tahunan', 'jersey_size' => 'XXL'],
            ['name' => 'Sari Wulandari', 'email' => 'sari.wulandari@example.com', 'phone' => '081234567820', 'gender' => 'female', 'membership_type' => 'setengah_tahun', 'jersey_size' => 'S'],
        ];

        foreach ($participants as $data) {
            $membershipType = $data['membership_type'];

            $username = $this->generateUsername($data['name']);

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'username' => $username,
                    'password' => Hash::make('password'),
                    'role' => 'participant',
                    'is_active' => true,
                ]
            );

            $participantData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'gender' => $data['gender'],
                'date_of_birth' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                'address' => fake()->address(),
                'emergency_contact' => fake()->name(),
                'emergency_phone' => '08'.fake()->numerify('##########'),
                'medical_conditions' => null,
                'blood_type' => fake()->randomElement(['A', 'B', 'AB', 'O']),
                'jersey_size' => $data['jersey_size'],
                'membership_type' => $membershipType,
                'membership_start_date' => now()->subDays(30)->toDateString(),
                'membership_end_date' => $membershipType !== 'none'
                    ? now()->addDays($memberships[$membershipType][1])->toDateString()
                    : null,
                'is_active' => true,
                'total_events_participated' => 0,
            ];

            $membership = Participant::updateOrCreate(
                ['email' => $data['email']],
                array_merge($participantData, ['user_id' => $user->id])
            );

            if ($membershipType !== 'none') {
                [$price, $days] = $memberships[$membershipType];

                MembershipHistory::updateOrCreate(
                    [
                        'participant_id' => $membership->id,
                        'membership_type' => $membershipType,
                        'start_date' => $membership->membership_start_date,
                    ],
                    [
                        'end_date' => $membership->membership_end_date,
                        'price' => $price,
                        'status' => 'active',
                    ]
                );
            }
        }
    }

    private function generateUsername(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name, '_');
        $base = str_replace('-', '_', $base);
        $base = preg_replace('/[^a-zA-Z0-9_]/', '', $base);
        $base = \Illuminate\Support\Str::lower($base);
        $base = substr($base, 0, 30);

        $username = $base;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $suffixPart = (string) $suffix;
            $maxLen = 30 - strlen($suffixPart) - 1;
            $truncated = $maxLen > 0 ? substr($base, 0, $maxLen) : substr($base, 0, 25);
            $username = $truncated.'_'.$suffixPart;
            $suffix++;
        }

        return $username;
    }
}
