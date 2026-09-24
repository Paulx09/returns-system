<?php

namespace Tests\Unit\Returns;

use App\Models\ExternalOrderCache;
use App\Services\ExternalOrderService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExternalOrderServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_order_is_eligible_on_the_last_day_of_the_return_period(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00'));
        $order = new ExternalOrderCache([
            'order_date' => Carbon::parse('2026-05-08 12:00:00'),
        ]);
        $service = new ExternalOrderService();

        // Act
        $isEligible = $service->isWithinReturnPeriod($order, 7);

        // Assert
        $this->assertTrue($isEligible);
    }

    public function test_order_is_ineligible_after_the_return_period(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00'));
        $order = new ExternalOrderCache([
            'order_date' => Carbon::parse('2026-05-07 12:00:00'),
        ]);
        $service = new ExternalOrderService();

        // Act
        $isEligible = $service->isWithinReturnPeriod($order, 7);

        // Assert
        $this->assertFalse($isEligible);
    }
}