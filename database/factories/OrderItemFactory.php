<?php

namespace Database\Factories;

use App\Models\ExternalOrderCache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => ExternalOrderCache::factory(),
            'product_code' => 'SKU-' . $this->faker->unique()->numberBetween(1000, 9999),
            'product_name' => $this->faker->words(3, true),
            'quantity' => $this->faker->numberBetween(1, 5),
            'unit_price' => $this->faker->randomFloat(2, 10, 500),
        ];
    }
}
