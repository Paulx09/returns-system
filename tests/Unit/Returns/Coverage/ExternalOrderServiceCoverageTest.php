<?php

namespace Tests\Unit\Returns\Coverage;

use App\Models\ExternalOrderCache;
use App\Services\ExternalOrderService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExternalOrderServiceCoverageTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[DataProvider('returnPeriodDecisionProvider')]
    public function test_return_period_decision_matches_the_business_rule(
        string $orderDate,
        int $daysLimit,
        bool $expectedEligibility,
    ): void {
        // Arrange
        Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00'));
        $order = new ExternalOrderCache([
            'order_date' => Carbon::parse($orderDate),
        ]);
        $service = new ExternalOrderService();

        // Act
        $isEligible = $service->isWithinReturnPeriod($order, $daysLimit);

        // Assert
        $this->assertSame($expectedEligibility, $isEligible);
    }

    /**
     * @return array<string, array{string, int, bool}>
     */
    public static function returnPeriodDecisionProvider(): array
    {
        return [
            'same day with zero-day limit' => ['2026-05-15 12:00:00', 0, true],
            'inside the configured period' => ['2026-05-09 12:00:00', 7, true],
            'exactly on the configured boundary' => ['2026-05-08 12:00:00', 7, true],
            'one day beyond the configured period' => ['2026-05-07 12:00:00', 7, false],
        ];
    }
}