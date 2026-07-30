<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin Full Access', 'email' => 'admin.full@sh3.com', 'role' => 'admin_full_access'],
            ['name' => 'Admin Laman', 'email' => 'admin.laman@sh3.com', 'role' => 'admin_laman'],
            ['name' => 'Admin Member', 'email' => 'admin.member@sh3.com', 'role' => 'admin_member'],
            ['name' => 'Admin BNH', 'email' => 'admin.bnh@sh3.com', 'role' => 'admin_bnh'],
            ['name' => 'Organizer', 'email' => 'organizer@sh3.com', 'role' => 'organizer'],
            ['name' => 'Bendahara', 'email' => 'bendahara@sh3.com', 'role' => 'bendahara'],
            ['name' => 'Sponsor', 'email' => 'sponsor@sh3.com', 'role' => 'sponsor'],
            ['name' => 'Merchandise', 'email' => 'merchandise@sh3.com', 'role' => 'merchandise'],
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make('password'),
                'role' => $user['role'],
                'is_active' => true,
            ]);
        }
    }
}
