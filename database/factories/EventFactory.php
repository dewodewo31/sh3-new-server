<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+1 month');

        return [
            'category_id' => \App\Models\Category::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'location' => fake()->city(),
            'address' => fake()->address(),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+3 hours'),
            'registration_start_date' => now()->subDays(5),
            'registration_end_date' => now()->addDays(5),
            'quota' => fake()->numberBetween(10, 100),
            'price' => fake()->numberBetween(0, 500000),
            'is_free_for_members' => true,
            'status' => 'publish',
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
