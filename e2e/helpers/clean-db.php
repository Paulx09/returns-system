<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ReturnTicket;
use App\Models\ReturnItem;
use App\Models\Evidence;
use App\Models\TicketStatusHistory;
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

echo "DB reset complete. Tickets: " . ReturnTicket::count() . PHP_EOL;
