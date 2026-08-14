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
            'base_event_price' => 25000,
            'discount_percentage' => fake()->randomElement([5, 10]),
            'reference_event_count' => fake()->randomElement([1, 26, 53]),
            'price' => fake()->randomElement([25000, 617500, 1192500]), // admin-defined final package price (stored as-is)
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
