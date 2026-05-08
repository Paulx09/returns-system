<?php

namespace Database\Seeders;

use App\Models\ExternalOrderCache;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class ExternalOrderCacheSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generamos órdenes recientes (dentro de los 7 días) y antiguas (fuera de los 7 días)
        
        // 5 órdenes válidas (< 7 días) con items
        ExternalOrderCache::factory()
            ->count(5)
            ->state(function (array $attributes) {
                return ['order_date' => fake()->dateTimeBetween('-6 days', 'now')];
            })
            ->has(OrderItem::factory()->count(3), 'orderItems')
            ->create();

        // 3 órdenes vencidas (> 7 días) con items
        ExternalOrderCache::factory()
            ->count(3)
            ->state(function (array $attributes) {
                return ['order_date' => fake()->dateTimeBetween('-30 days', '-8 days')];
            })
            ->has(OrderItem::factory()->count(2), 'orderItems')
            ->create();
            
        // 1 orden específica para pruebas E2E/Manuales
        $order = ExternalOrderCache::factory()->create([
            'order_number' => 'ORD-123456',
            'customer_dni' => '12345678',
            'order_date' => now()->subDays(2),
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->order_id,
            'product_name' => 'Mochila Escolar Spider-Man',
        ]);
    }
}
