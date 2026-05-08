<?php

namespace Database\Factories;

use App\Models\ReturnTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnTicket>
 */
class ReturnTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracking_code'  => 'RET-' . strtoupper(fake()->unique()->bothify('########')),
            'order_id'       => \App\Models\ExternalOrderCache::factory(),
            'current_status' => fake()->randomElement(\App\Models\ReturnTicket::STATUSES),
            'customer_comment' => fake()->optional()->sentence(),
            'created_by_user_id' => null,
        ];
    }
}
