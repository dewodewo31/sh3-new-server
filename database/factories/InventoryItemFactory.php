<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'asset_code' => strtoupper(fake()->unique()->bothify('AST-####')),
            'name' => fake()->words(3, true),
            'category_id' => null,
            'brand' => fake()->optional()->company(),
            'model' => fake()->optional()->word(),
            'serial_number' => null,
            'description' => fake()->optional()->sentence(),
            'purchase_date' => fake()->optional()->date(),
            'purchase_price' => fake()->optional()->randomFloat(2, 100000, 10000000),
            'warranty_expiry' => null,
            'status' => InventoryItem::STATUS_AVAILABLE,
            'condition' => InventoryItem::CONDITION_GOOD,
            'location' => fake()->optional()->city(),
            'notes' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
