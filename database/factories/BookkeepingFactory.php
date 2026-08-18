<?php

namespace Database\Factories;

use App\Models\Bookkeeping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bookkeeping>
 */
class BookkeepingFactory extends Factory
{
    protected $model = Bookkeeping::class;

    public function definition(): array
    {
        return [
            'transaction_date' => now()->toDateString(),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['income', 'expense']),
            'amount' => fake()->numberBetween(10000, 10000000),
            'category' => fake()->randomElement(['sponsor', 'event_income', 'other']),
            'sponsor_id' => null,
            'event_id' => null,
            'receipt' => null,
            'created_by' => null,
        ];
    }
}
