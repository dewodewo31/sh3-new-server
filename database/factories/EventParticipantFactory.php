<?php

namespace Database\Factories;

use App\Models\EventParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventParticipant>
 */
class EventParticipantFactory extends Factory
{
    protected $model = EventParticipant::class;

    public function definition(): array
    {
        return [
            'event_id' => \App\Models\Event::factory(),
            'participant_id' => \App\Models\Participant::factory(),
            'registration_type' => 'free',
            'amount' => null,
            'payment_status' => 'confirmed',
            'is_attended' => false,
            'check_in_at' => null,
            'check_out_at' => null,
            'qr_code' => null,
            'is_membership_free' => false,
            'payment_id' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['payment_status' => 'confirmed']);
    }

    public function attended(): static
    {
        return $this->state(fn () => [
            'is_attended' => true,
            'check_in_at' => now()->subHours(2),
        ]);
    }
}
