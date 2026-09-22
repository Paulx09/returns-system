<?php

namespace Tests\Unit\Models;

use App\Models\OrderItem;
use App\Models\ReturnItem;
use App\Models\ReturnReason;
use App\Models\ReturnTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReturnItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_be_created_with_uuid(): void
    {
        $returnItem = ReturnItem::factory()->create();

        $this->assertDatabaseHas('return_items', [
            'return_item_id' => $returnItem->return_item_id,
        ]);

        $this->assertTrue(Str::isUuid((string) $returnItem->return_item_id));
    }

    public function test_it_belongs_to_ticket_order_item_and_reason(): void
    {
        $ticket = ReturnTicket::factory()->create();
        $orderItem = OrderItem::factory()->create();
        $reason = ReturnReason::factory()->create();

        $returnItem = ReturnItem::factory()->create([
            'ticket_id' => $ticket->ticket_id,
            'order_item_id' => $orderItem->order_item_id,
            'reason_id' => $reason->reason_id,
        ]);

        $this->assertInstanceOf(ReturnTicket::class, $returnItem->ticket);
        $this->assertInstanceOf(OrderItem::class, $returnItem->orderItem);
        $this->assertInstanceOf(ReturnReason::class, $returnItem->reason);
    }
}
