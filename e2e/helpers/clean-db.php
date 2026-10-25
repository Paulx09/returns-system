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

// 1. Garantizar orden elegible (< 7 días) para Happy Path y consultas E2E
$validOrder = ExternalOrderCache::updateOrCreate(
    ['order_number' => 'ORD-123456'],
    [
        'customer_full_name' => 'Cliente Elegible',
        'customer_dni' => '12345678',
        'customer_email' => 'cliente@example.com',
        'order_date' => now()->subDays(2),
    ]
);

if ($validOrder->orderItems()->count() === 0) {
    OrderItem::create([
        'order_id' => $validOrder->order_id,
        'product_name' => 'Mochila Escolar Spider-Man',
        'product_code' => 'MOC-SPD-01',
        'quantity' => 2,
        'unit_price' => 89.90,
    ]);
}

// 2. Garantizar orden vencida (> 7 días) para pruebas de bloqueo de plazo
ExternalOrderCache::updateOrCreate(
    ['order_number' => 'ORD-290146'],
    [
        'customer_full_name' => 'Cliente Vencido',
        'customer_dni' => '40112710',
        'customer_email' => 'cliente.vencido@example.com',
        'order_date' => now()->subDays(15),
    ]
);

echo "DB reset complete. Tickets: " . ReturnTicket::count() . PHP_EOL;
