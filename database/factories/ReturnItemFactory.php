<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\ReturnItem;
use App\Models\ReturnReason;
use App\Models\ReturnTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnItem>
 */
class ReturnItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => ReturnTicket::factory(),
            'order_item_id' => OrderItem::factory(),
            'reason_id' => ReturnReason::factory(),
            'quantity_to_return' => fake()->numberBetween(1, 3),
            'admin_comment' => fake()->optional()->sentence(),
        ];
    }
}
