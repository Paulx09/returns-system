<?php

namespace Database\Seeders;

use App\Models\Evidence;
use App\Models\ExternalOrderCache;
use App\Models\OrderItem;
use App\Models\ReturnItem;
use App\Models\ReturnReason;
use App\Models\ReturnTicket;
use App\Models\TicketStatusHistory;
use Illuminate\Database\Seeder;

class ExternalOrderCacheSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 5 órdenes válidas (< 7 días) con items aleatorios
        ExternalOrderCache::factory()
            ->count(5)
            ->state(function (array $attributes) {
                return ['order_date' => fake()->dateTimeBetween('-6 days', 'now')];
            })
            ->has(OrderItem::factory()->count(3), 'orderItems')
            ->create();

        // 3 órdenes vencidas (> 7 días) con items aleatorios
        ExternalOrderCache::factory()
            ->count(3)
            ->state(function (array $attributes) {
                return ['order_date' => fake()->dateTimeBetween('-30 days', '-8 days')];
            })
            ->has(OrderItem::factory()->count(2), 'orderItems')
            ->create();
            
        // 1. Orden específica para pruebas E2E/Manuales/API (ORD-123456)
        $order = ExternalOrderCache::updateOrCreate(
            ['order_number' => 'ORD-123456'],
            [
                'customer_full_name' => 'Cliente Elegible',
                'customer_dni' => '12345678',
                'customer_email' => 'cliente@example.com',
                'order_date' => now()->subDays(2),
            ]
        );

        // Identificar claves foráneas dependientes
        $targetOrderItemIds = OrderItem::where('order_id', $order->order_id)
            ->orWhere('order_item_id', '01a11f3f-8c38-7046-b642-d7a23e8d4080')
            ->pluck('order_item_id');

        $relatedTicketIds = ReturnTicket::where('order_id', $order->order_id)
            ->pluck('ticket_id')
            ->merge(ReturnItem::whereIn('order_item_id', $targetOrderItemIds)->pluck('ticket_id'))
            ->unique()
            ->filter();

        // Jerarquía de eliminación de hijos a padres para garantizar integridad referencial (evitar FK violation)
        if ($relatedTicketIds->isNotEmpty()) {
            TicketStatusHistory::whereIn('ticket_id', $relatedTicketIds)->forceDelete();
            Evidence::whereIn('ticket_id', $relatedTicketIds)->forceDelete();
            ReturnItem::whereIn('ticket_id', $relatedTicketIds)->forceDelete();
            ReturnTicket::whereIn('ticket_id', $relatedTicketIds)->forceDelete();
        }

        ReturnItem::whereIn('order_item_id', $targetOrderItemIds)->forceDelete();
        OrderItem::whereIn('order_item_id', $targetOrderItemIds)->forceDelete();

        // Crear único ítem canónico para ORD-123456
        OrderItem::create([
            'order_item_id' => '01a11f3f-8c38-7046-b642-d7a23e8d4080',
            'order_id' => $order->order_id,
            'product_name' => 'Mochila Escolar Spider-Man',
            'product_code' => 'MOC-SPD-01',
            'quantity' => 2,
            'unit_price' => 89.90,
        ]);

        // 2. Orden vencida para pruebas de frontera (> 7 días) (ORD-290146)
        $expiredOrder = ExternalOrderCache::updateOrCreate(
            ['order_number' => 'ORD-290146'],
            [
                'customer_full_name' => 'Cliente Vencido',
                'customer_dni' => '40112710',
                'customer_email' => 'cliente.vencido@example.com',
                'order_date' => now()->subDays(15),
            ]
        );

        $expiredOrderItemIds = OrderItem::where('order_id', $expiredOrder->order_id)->pluck('order_item_id');
        $expiredTicketIds = ReturnTicket::where('order_id', $expiredOrder->order_id)
            ->pluck('ticket_id')
            ->merge(ReturnItem::whereIn('order_item_id', $expiredOrderItemIds)->pluck('ticket_id'))
            ->unique()
            ->filter();

        if ($expiredTicketIds->isNotEmpty()) {
            TicketStatusHistory::whereIn('ticket_id', $expiredTicketIds)->forceDelete();
            Evidence::whereIn('ticket_id', $expiredTicketIds)->forceDelete();
            ReturnItem::whereIn('ticket_id', $expiredTicketIds)->forceDelete();
            ReturnTicket::whereIn('ticket_id', $expiredTicketIds)->forceDelete();
        }

        ReturnItem::whereIn('order_item_id', $expiredOrderItemIds)->forceDelete();
        OrderItem::whereIn('order_item_id', $expiredOrderItemIds)->forceDelete();

        // 3. Orden con ticket activo en curso para pruebas de redirección y tracking (ORD-ACTIVE-01)
        $activeOrder = ExternalOrderCache::updateOrCreate(
            ['order_number' => 'ORD-ACTIVE-01'],
            [
                'customer_full_name' => 'Cliente Trámite Activo',
                'customer_dni' => '12345678',
                'customer_email' => 'cliente.activo@example.com',
                'order_date' => now()->subDays(1),
            ]
        );

        $activeOrderItemIds = OrderItem::where('order_id', $activeOrder->order_id)->pluck('order_item_id');
        $activeTicketIds = ReturnTicket::withTrashed()
            ->where('order_id', $activeOrder->order_id)
            ->orWhere('tracking_code', 'RET-12345678')
            ->pluck('ticket_id')
            ->merge(ReturnItem::whereIn('order_item_id', $activeOrderItemIds)->pluck('ticket_id'))
            ->unique()
            ->filter();

        if ($activeTicketIds->isNotEmpty()) {
            TicketStatusHistory::whereIn('ticket_id', $activeTicketIds)->forceDelete();
            Evidence::whereIn('ticket_id', $activeTicketIds)->forceDelete();
            ReturnItem::whereIn('ticket_id', $activeTicketIds)->forceDelete();
            ReturnTicket::withTrashed()->whereIn('ticket_id', $activeTicketIds)->forceDelete();
        }

        ReturnItem::whereIn('order_item_id', $activeOrderItemIds)->forceDelete();
        OrderItem::whereIn('order_item_id', $activeOrderItemIds)->forceDelete();

        $activeOrderItem = OrderItem::create([
            'order_id' => $activeOrder->order_id,
            'product_name' => 'Calculadora Científica Casio FX-991LAX',
            'product_code' => 'CAL-CAS-991',
            'quantity' => 1,
            'unit_price' => 129.90,
        ]);

        $defaultReason = ReturnReason::first() ?? ReturnReason::create([
            'reason_id' => '01a11f3f-79f9-7386-9097-067c1476944e',
            'name' => 'Producto Dañado',
            'description' => 'El producto llegó con daños físicos o de fábrica.',
        ]);

        $activeTicket = ReturnTicket::create([
            'order_id' => $activeOrder->order_id,
            'tracking_code' => 'RET-12345678',
            'current_status' => 'received',
            'customer_comment' => 'La pantalla presenta líneas defectuosas al encender.',
        ]);

        ReturnItem::create([
            'ticket_id' => $activeTicket->ticket_id,
            'order_item_id' => $activeOrderItem->order_item_id,
            'reason_id' => $defaultReason->reason_id,
            'quantity_to_return' => 1,
            'condition' => 'damaged',
        ]);

        TicketStatusHistory::create([
            'ticket_id' => $activeTicket->ticket_id,
            'old_status' => null,
            'new_status' => 'received',
            'changed_by_user_id' => null,
            'comment' => 'Solicitud de devolución registrada en el portal.',
        ]);
    }
}
