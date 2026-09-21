<?php

namespace Tests\Unit\Models;

use App\Models\Evidence;
use App\Models\ReturnTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_be_created_with_uuid(): void
    {
        $evidence = Evidence::factory()->create();

        $this->assertDatabaseHas('evidences', [
            'evidence_id' => $evidence->evidence_id,
        ]);

        $this->assertTrue(Str::isUuid((string) $evidence->evidence_id));
    }

    public function test_it_belongs_to_return_ticket(): void
    {
        $ticket = ReturnTicket::factory()->create();
        $evidence = Evidence::factory()->create([
            'ticket_id' => $ticket->ticket_id,
        ]);

        $this->assertInstanceOf(ReturnTicket::class, $evidence->ticket);
        $this->assertEquals($ticket->ticket_id, $evidence->ticket->ticket_id);
    }
}
