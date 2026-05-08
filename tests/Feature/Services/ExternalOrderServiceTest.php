<?php

namespace Tests\Feature\Services;

use App\Models\ExternalOrderCache;
use App\Services\ExternalOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExternalOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExternalOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExternalOrderService();
    }

    public function test_can_find_order_by_number_and_dni(): void
    {
        $order = ExternalOrderCache::factory()->create([
            'order_number' => 'ORD-TEST',
            'customer_dni' => '12345678',
        ]);

        $found = $this->service->findOrder('ORD-TEST', '12345678');

        $this->assertNotNull($found);
        $this->assertEquals($order->order_id, $found->order_id);
    }

    public function test_returns_null_for_invalid_credentials(): void
    {
        ExternalOrderCache::factory()->create([
            'order_number' => 'ORD-TEST',
            'customer_dni' => '12345678',
        ]);

        $this->assertNull($this->service->findOrder('ORD-TEST', '87654321')); // Wrong DNI
        $this->assertNull($this->service->findOrder('ORD-WRONG', '12345678')); // Wrong Order
    }

    public function test_verifies_if_order_is_within_return_period(): void
    {
        Carbon::setTestNow('2026-05-15 12:00:00');

        $validOrder = ExternalOrderCache::factory()->create([
            'order_date' => Carbon::now()->subDays(6),
        ]);

        $expiredOrder = ExternalOrderCache::factory()->create([
            'order_date' => Carbon::now()->subDays(8),
        ]);

        $this->assertTrue($this->service->isWithinReturnPeriod($validOrder, 7));
        $this->assertFalse($this->service->isWithinReturnPeriod($expiredOrder, 7));
    }
}
