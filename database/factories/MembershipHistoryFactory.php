<?php

namespace Database\Factories;

use App\Models\MembershipHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipHistory>
 */
class MembershipHistoryFactory extends Factory
{
    protected $model = MembershipHistory::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['tahunan', 'setengah_tahun', 'mingguan']);

        return [
            'participant_id' => \App\Models\Participant::factory(),
            'membership_type' => $type,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(12)->toDateString(),
            'price' => fake()->randomElement([10000, 250000, 400000]),
            'status' => 'active',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'start_date' => now()->subMonths(12)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);
    }
}
