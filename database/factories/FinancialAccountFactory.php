<?php

namespace Database\Factories;

use App\Models\FinancialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialAccount>
 */
class FinancialAccountFactory extends Factory
{
    protected $model = FinancialAccount::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'type' => fake()->randomElement(['kas', 'bank', 'piutang', 'hutang', 'pendapatan', 'beban']),
            'is_active' => true,
        ];
    }
}
