<?php

namespace Tests\Feature\Returns;

use App\Models\ExternalOrderCache;
use App\Models\OrderItem;
use App\Models\ReturnReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_order_items_and_reasons_without_an_n_plus_one_query(): void
    {
        // Arrange
        $order = ExternalOrderCache::factory()->create();
        OrderItem::factory()->count(3)->create(['order_id' => $order->order_id]);
        ReturnReason::factory()->count(3)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act
        $response = $this->withSession(['customer_order_id' => $order->order_id])
            ->get('/returns/dashboard');

        // Assert
        $response->assertOk();
        $this->assertCount(3, DB::getQueryLog());
    }
}