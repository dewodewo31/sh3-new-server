<?php

namespace Database\Seeders;

use App\Models\Bookkeeping;
use App\Models\Event;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookkeepingSeeder extends Seeder
{
    public function run(): void
    {
        $createdBy = User::where('role', 'bendahara')->first()?->id ?? User::first()?->id;
        $sponsor = Sponsor::first();
        $event = Event::first();

        $entries = [
            [
                'description' => 'Sponsorship Nike Indonesia',
                'type' => 'income',
                'amount' => 500000000,
                'category' => 'sponsor',
                'sponsor_id' => $sponsor?->id,
                'event_id' => null,
                'transaction_date' => now()->subDays(10)->toDateString(),
            ],
            [
                'description' => 'Pendapatan registrasi event City Run Sudirman',
                'type' => 'income',
                'amount' => 75000000,
                'category' => 'event_income',
                'sponsor_id' => null,
                'event_id' => $event?->id,
                'transaction_date' => now()->subDays(7)->toDateString(),
            ],
            [
                'description' => 'Sewa tenda',
                'type' => 'expense',
                'amount' => 15000000,
                'category' => 'other',
                'sponsor_id' => null,
                'event_id' => null,
                'transaction_date' => now()->subDays(5)->toDateString(),
            ],
            [
                'description' => 'Konsumsi panitia',
                'type' => 'expense',
                'amount' => 8000000,
                'category' => 'other',
                'sponsor_id' => null,
                'event_id' => null,
                'transaction_date' => now()->subDays(3)->toDateString(),
            ],
            [
                'description' => 'Iuran anggota',
                'type' => 'income',
                'amount' => 15000000,
                'category' => 'other',
                'sponsor_id' => null,
                'event_id' => null,
                'transaction_date' => now()->subDays(1)->toDateString(),
            ],
        ];

        foreach ($entries as $data) {
            Bookkeeping::updateOrCreate(
                ['description' => $data['description']],
                array_merge($data, ['receipt' => null, 'created_by' => $createdBy])
            );
        }
    }
}
