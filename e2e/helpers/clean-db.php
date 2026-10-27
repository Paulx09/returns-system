<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ReturnTicket;
use App\Models\ReturnItem;
use App\Models\Evidence;
use App\Models\TicketStatusHistory;
use App\Models\ExternalOrderCache;
use App\Models\OrderItem;
use App\Models\ReturnReason;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

TicketStatusHistory::truncate();
Evidence::truncate();
ReturnItem::truncate();
ReturnTicket::truncate();

try {
    Cache::flush();
    DB::table('sessions')->truncate();
} catch (\Throwable $e) {
    // continue
}

// 1. Eliminar y volver a crear el motivo canónico para asegurar UUID limpio y evitar colisiones
ReturnReason::where('name', 'Producto Dañado')
    ->orWhere('reason_id', '01a11f3f-79f9-7386-9097-067c1476944e')
    ->forceDelete();

ReturnReason::create([
    'reason_id' => '01a11f3f-79f9-7386-9097-067c1476944e',
    'name' => 'Producto Dañado',
    'description' => 'El producto llegó con daños físicos o de fábrica.',
]);

// 2. Garantizar orden elegible (< 7 días) para Happy Path y consultas E2E
$validOrder = ExternalOrderCache::updateOrCreate(
    ['order_number' => 'ORD-123456'],
    [
        'customer_full_name' => 'Cliente Elegible',
        'customer_dni' => '12345678',
        'customer_email' => 'cliente@example.com',
        'order_date' => now()->subDays(2),
    ]
);

// Eliminar todos los items previos de la orden para evitar duplicados en la UI
OrderItem::where('order_id', $validOrder->order_id)
    ->orWhere('order_item_id', '01a11f3f-8c38-7046-b642-d7a23e8d4080')
    ->forceDelete();

OrderItem::create([
    'order_item_id' => '01a11f3f-8c38-7046-b642-d7a23e8d4080',
    'order_id' => $validOrder->order_id,
    'product_name' => 'Mochila Escolar Spider-Man',
    'product_code' => 'MOC-SPD-01',
    'quantity' => 2,
    'unit_price' => 89.90,
]);

// 3. Garantizar orden vencida (> 7 días) para pruebas de bloqueo de plazo
$expiredOrder = ExternalOrderCache::updateOrCreate(
    ['order_number' => 'ORD-290146'],
    [
        'customer_full_name' => 'Cliente Vencido',
        'customer_dni' => '40112710',
        'customer_email' => 'cliente.vencido@example.com',
        'order_date' => now()->subDays(15),
    ]
);

OrderItem::where('order_id', $expiredOrder->order_id)->forceDelete();

echo "DB reset complete. Tickets: " . ReturnTicket::count() . PHP_EOL;
