<?php

namespace Database\Factories;

use App\Models\Evidence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evidence>
 */
class EvidenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => \App\Models\ReturnTicket::factory(),
            'file_name' => fake()->word() . '.jpg',
            'file_path' => 'evidences/' . fake()->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
        ];
    }
}
