<?php

namespace Tests\Unit\Models;

use App\Models\ReturnTicket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_be_created_with_uuid(): void
    {
        $history = TicketStatusHistory::factory()->create();

        $this->assertDatabaseHas('ticket_status_history', [
            'history_id' => $history->history_id,
        ]);

        $this->assertTrue(Str::isUuid((string) $history->history_id));
    }

    public function test_it_belongs_to_return_ticket(): void
    {
        $ticket = ReturnTicket::factory()->create();
        $history = TicketStatusHistory::factory()->create([
            'ticket_id' => $ticket->ticket_id,
        ]);

        $this->assertInstanceOf(ReturnTicket::class, $history->ticket);
        $this->assertEquals($ticket->ticket_id, $history->ticket->ticket_id);
    }

    public function test_it_belongs_to_changed_by_user(): void
    {
        $user = User::factory()->create();
        $history = TicketStatusHistory::factory()->create([
            'changed_by_user_id' => $user->user_id,
        ]);

        $this->assertInstanceOf(User::class, $history->changedBy);
        $this->assertEquals($user->user_id, $history->changedBy->user_id);
    }
}
