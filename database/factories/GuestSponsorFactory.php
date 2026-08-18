<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\GuestSponsor;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestSponsor>
 */
class GuestSponsorFactory extends Factory
{
    protected $model = GuestSponsor::class;

    public function definition(): array
    {
        $event = Event::factory()->create();

        return [
            'user_id' => User::factory()->create(['role' => 'guest_sponsor'])->id,
            'sponsor_id' => Sponsor::factory()->create()->id,
            'event_id' => $event->id,
            'qr_code' => fake()->unique()->numerify('GS-###-###-####'),
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => $event->end_date?->toDateString(),
            'is_active' => true,
            'created_by' => null,
        ];
    }
}
