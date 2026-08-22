<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventBudget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventBudget>
 */
class EventBudgetFactory extends Factory
{
    protected $model = EventBudget::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'activity_id' => Activity::factory(),
            'amount' => fake()->randomFloat(2, 1000, 10000000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
