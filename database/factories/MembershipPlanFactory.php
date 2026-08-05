<?php

namespace Database\Factories;

use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    protected $model = MembershipPlan::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'key' => Str::slug($name),
            'name' => $name,
            'description' => fake()->sentence(),
            'price' => fake()->randomElement([10000, 250000, 400000]),
            'duration' => fake()->randomElement([7, 30, 365]),
            'duration_unit' => 'days',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 5),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
