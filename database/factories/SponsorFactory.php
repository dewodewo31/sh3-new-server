<?php

namespace Database\Factories;

use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'logo' => null,
            'website' => fake()->url(),
            'contact_person' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'tier' => fake()->randomElement(['platinum', 'gold', 'silver', 'bronze', 'media_partner']),
            'year' => now()->year,
            'sponsorship_value' => fake()->numberBetween(1000000, 100000000),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
            'created_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
