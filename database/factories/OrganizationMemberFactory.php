<?php

namespace Database\Factories;

use App\Models\OrganizationMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationMember>
 */
class OrganizationMemberFactory extends Factory
{
    protected $model = OrganizationMember::class;

    public function definition(): array
    {
        return [
            'participant_id' => null,
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'role_description' => fake()->sentence(),
            'avatar' => null,
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
            'period_start' => now()->startOfYear()->toDateString(),
            'period_end' => now()->endOfYear()->toDateString(),
            'parent_id' => null,
            'level' => 0,
        ];
    }

    public function childOf(OrganizationMember $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            'level' => $parent->level + 1,
        ]);
    }
}
