<?php

namespace Database\Factories;

use App\Models\MerchandiseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchandiseOrder>
 */
class MerchandiseOrderFactory extends Factory
{
    protected $model = MerchandiseOrder::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);

        return [
            'merchandise_id' => \App\Models\Merchandise::factory(),
            'participant_id' => \App\Models\Participant::factory(),
            'customer_name' => fake()->name(),
            'customer_contact' => fake()->phoneNumber(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'quantity' => $quantity,
            'total_price' => $quantity * fake()->numberBetween(50000, 200000),
            'payment_status' => 'pending',
            'payment_id' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['payment_status' => 'paid']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['payment_status' => 'cancelled']);
    }
}
