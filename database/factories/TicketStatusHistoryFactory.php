<?php

namespace Database\Factories;

use App\Models\ReturnTicket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketStatusHistory>
 */
class TicketStatusHistoryFactory extends Factory
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
            'old_status' => null,
            'new_status' => 'received',
            'changed_by_user_id' => User::factory(),
            'comment' => fake()->sentence(),
        ];
    }
}
