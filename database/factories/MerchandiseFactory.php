<?php

namespace Database\Factories;

use App\Models\Merchandise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Merchandise>
 */
class MerchandiseFactory extends Factory
{
    protected $model = Merchandise::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word().' SH3',
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(50000, 500000),
            'size_options' => ['S', 'M', 'L', 'XL'],
            'stock' => fake()->numberBetween(1, 50),
            'image' => null,
            'status' => 'available',
            'created_by' => null,
        ];
    }

    public function available(): static
    {
        return $this->state(fn () => ['status' => 'available']);
    }

    public function soldOut(): static
    {
        return $this->state(fn () => ['status' => 'sold_out', 'stock' => 0]);
    }

    public function discontinued(): static
    {
        return $this->state(fn () => ['status' => 'discontinued']);
    }
}
