<?php

namespace Tests\Unit\Models;

use App\Models\ExternalOrderCache;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_be_created_with_uuid(): void
    {
        $orderItem = OrderItem::factory()->create();

        $this->assertDatabaseHas('order_items', [
            'order_item_id' => $orderItem->order_item_id,
        ]);

        $this->assertTrue(Str::isUuid((string) $orderItem->order_item_id));
    }

    public function test_it_belongs_to_an_order(): void
    {
        $order = ExternalOrderCache::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->order_id,
        ]);

        $this->assertInstanceOf(ExternalOrderCache::class, $orderItem->order);
        $this->assertEquals($order->order_id, $orderItem->order->order_id);
    }
}
