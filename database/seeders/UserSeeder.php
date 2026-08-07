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
            ['name' => 'Admin Full Access', 'username' => 'admin_full', 'email' => 'admin.full@sh3.com', 'role' => 'admin_full_access'],
            ['name' => 'Admin Laman', 'username' => 'admin_laman', 'email' => 'admin.laman@sh3.com', 'role' => 'admin_laman'],
            ['name' => 'Admin Member', 'username' => 'admin_member', 'email' => 'admin.member@sh3.com', 'role' => 'admin_member'],
            ['name' => 'Admin BNH', 'username' => 'admin_bnh', 'email' => 'admin.bnh@sh3.com', 'role' => 'admin_bnh'],
            ['name' => 'Organizer', 'username' => 'organizer', 'email' => 'organizer@sh3.com', 'role' => 'organizer'],
            ['name' => 'Bendahara', 'username' => 'bendahara', 'email' => 'bendahara@sh3.com', 'role' => 'bendahara'],
            ['name' => 'Sponsor', 'username' => 'sponsor', 'email' => 'sponsor@sh3.com', 'role' => 'sponsor'],
            ['name' => 'Merchandise', 'username' => 'merchandise', 'email' => 'merchandise@sh3.com', 'role' => 'merchandise'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'is_active' => true,
                ]
            );
        }
    }
}
