<?php

namespace Tests\Feature\Models;

use App\Models\ExternalOrderCache;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExternalOrderCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_be_created_with_uuid(): void
    {
        $order = ExternalOrderCache::factory()->create();

        $this->assertDatabaseHas('external_orders_cache', [
            'order_id' => $order->order_id,
        ]);
        
        $this->assertTrue(Str::isUuid((string) $order->order_id));
    }

    public function test_it_has_many_order_items(): void
    {
        $order = ExternalOrderCache::factory()->create();
        $items = OrderItem::factory()->count(3)->create([
            'order_id' => $order->order_id,
        ]);

        $this->assertCount(3, $order->orderItems);
        $this->assertInstanceOf(OrderItem::class, $order->orderItems->first());
    }

    public function test_it_uses_soft_deletes(): void
    {
        $order = ExternalOrderCache::factory()->create();
        
        $order->delete();

        $this->assertSoftDeleted('external_orders_cache', [
            'order_id' => $order->order_id,
        ]);
    }
}
