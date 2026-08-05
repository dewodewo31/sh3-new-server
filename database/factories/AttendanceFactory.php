<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'event_participant_id' => \App\Models\EventParticipant::factory(),
            'check_in_time' => null,
            'check_out_time' => null,
            'status' => 'absent',
            'latitude' => null,
            'longitude' => null,
            'check_in_method' => 'qr_code',
            'notes' => null,
        ];
    }

    public function present(): static
    {
        return $this->state(fn () => [
            'status' => 'present',
            'check_in_time' => now()->subHours(2),
        ]);
    }
}
