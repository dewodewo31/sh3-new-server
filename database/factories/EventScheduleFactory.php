<?php

namespace Database\Factories;

use App\Models\EventSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSchedule>
 */
class EventScheduleFactory extends Factory
{
    protected $model = EventSchedule::class;

    public function definition(): array
    {
        $start = now()->addDays(10)->setTime(6, 0);

        return [
            'event_id' => \App\Models\Event::factory(),
            'title' => fake()->sentence(2),
            'description' => fake()->sentence(),
            'start_time' => $start,
            'end_time' => (clone $start)->modify('+2 hours'),
            'order_number' => fake()->numberBetween(0, 10),
        ];
    }
}
