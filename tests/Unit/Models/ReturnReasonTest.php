<?php

namespace Tests\Unit\Models;

use App\Models\ReturnReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReturnReasonTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_be_created_with_uuid(): void
    {
        $reason = ReturnReason::factory()->create();

        $this->assertDatabaseHas('return_reasons', [
            'reason_id' => $reason->reason_id,
        ]);

        $this->assertTrue(Str::isUuid((string) $reason->reason_id));
    }
}
