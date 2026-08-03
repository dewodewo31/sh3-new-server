<?php

namespace Database\Factories;

use App\Models\AttendanceLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceLog>
 */
class AttendanceLogFactory extends Factory
{
    protected $model = AttendanceLog::class;

    public function definition(): array
    {
        return [
            'event_id' => \App\Models\Event::factory(),
            'participant_id' => \App\Models\Participant::factory(),
            'type' => fake()->randomElement(['check_in', 'check_out']),
            'scan_time' => now(),
            'scanned_by' => null,
            'qr_code' => null,
            'latitude' => null,
            'longitude' => null,
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function checkIn(): static
    {
        return $this->state(fn () => ['type' => 'check_in']);
    }

    public function checkOut(): static
    {
        return $this->state(fn () => ['type' => 'check_out']);
    }
}
