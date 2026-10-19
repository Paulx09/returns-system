<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ReturnTicket;
use App\Models\ExternalOrderCache;

$order = ExternalOrderCache::where('order_number', 'ORD-566677')->first();
if (!$order) {
    echo "Order ORD-566677 not found, exiting.\n";
    exit(0);
}

$ticket = ReturnTicket::firstOrCreate(
    ['tracking_code' => 'RET-56667701'],
    [
        'order_id' => $order->order_id,
        'current_status' => 'received',
        'customer_comment' => 'Seeded for k6 performance testing',
    ]
);

echo "Ticket ready: {$ticket->tracking_code} (Status: {$ticket->current_status}, Order: {$order->order_number})\n";
