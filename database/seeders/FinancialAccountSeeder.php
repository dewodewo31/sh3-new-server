<?php

namespace Database\Seeders;

use App\Models\FinancialAccount;
use Illuminate\Database\Seeder;

class FinancialAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'Kas', 'type' => 'kas'],
            ['name' => 'Bank', 'type' => 'bank'],
            ['name' => 'Piutang', 'type' => 'piutang'],
            ['name' => 'Hutang', 'type' => 'hutang'],
            ['name' => 'Pendapatan', 'type' => 'pendapatan'],
            ['name' => 'Beban', 'type' => 'beban'],
        ];

        foreach ($accounts as $account) {
            FinancialAccount::firstOrCreate(
                ['name' => $account['name']],
                ['type' => $account['type'], 'is_active' => true]
            );
        }
    }
}
