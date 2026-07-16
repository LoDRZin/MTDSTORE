<?php

namespace Database\Factories;

use App\Models\ProductStockItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductStockItem>
 */
class ProductStockItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'batch_id' => \Illuminate\Support\Str::uuid(),
            'value' => $this->faker->uuid,
            'status' => 'available',
        ];
    }
}
