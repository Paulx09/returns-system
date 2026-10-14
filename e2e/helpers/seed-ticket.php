<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ReturnTicket;
use App\Models\ReturnItem;
use App\Models\Evidence;
use App\Models\TicketStatusHistory;
use App\Models\ExternalOrderCache;
use App\Models\ReturnReason;
use App\Models\User;

$targetStatus = $argv[1] ?? 'received';

$order = ExternalOrderCache::where('order_number', 'ORD-123456')->firstOrFail();
$reason = ReturnReason::firstOrFail();
$admin = User::where('role', 'admin')->firstOrFail();

$ticket = ReturnTicket::create([
    'order_id' => $order->order_id,
    'tracking_code' => 'RET-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
    'current_status' => $targetStatus,
    'customer_comment' => 'Prueba automatizada Playwright E2E',
]);

$orderItem = $order->orderItems()->first();
if ($orderItem) {
    ReturnItem::create([
        'ticket_id' => $ticket->ticket_id,
        'order_item_id' => $orderItem->order_item_id,
        'reason_id' => $reason->reason_id,
        'quantity_to_return' => 1,
        'condition' => 'opened',
    ]);
}

Evidence::create([
    'ticket_id' => $ticket->ticket_id,
    'file_path' => 'evidences/sample.jpg',
    'file_name' => 'sample_evidence.jpg',
    'mime_type' => 'image/jpeg',
    'file_size_bytes' => 1024,
]);

TicketStatusHistory::create([
    'ticket_id' => $ticket->ticket_id,
    'old_status' => null,
    'new_status' => 'received',
    'changed_by_user_id' => null,
    'comment' => 'Ticket creado para test E2E',
    'changed_at' => now(),
]);

if ($targetStatus !== 'received') {
    TicketStatusHistory::create([
        'ticket_id' => $ticket->ticket_id,
        'old_status' => 'received',
        'new_status' => $targetStatus,
        'changed_by_user_id' => $admin->user_id,
        'comment' => 'Transición automática para pruebas',
        'changed_at' => now(),
    ]);
}

echo json_encode([
    'ticket_id' => $ticket->ticket_id,
    'tracking_code' => $ticket->tracking_code,
    'status' => $ticket->current_status,
    'order_number' => $order->order_number,
    'customer_dni' => $order->customer_dni,
]);
