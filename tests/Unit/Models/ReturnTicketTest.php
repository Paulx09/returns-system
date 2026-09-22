<?php

namespace Tests\Unit\Models;

use App\Models\Evidence;
use App\Models\ExternalOrderCache;
use App\Models\ReturnItem;
use App\Models\ReturnTicket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReturnTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_be_created_with_uuid(): void
    {
        $ticket = ReturnTicket::factory()->create();

        $this->assertDatabaseHas('return_tickets', [
            'ticket_id' => $ticket->ticket_id,
        ]);

        $this->assertTrue(Str::isUuid((string) $ticket->ticket_id));
    }

    public function test_it_belongs_to_an_order(): void
    {
        $order = ExternalOrderCache::factory()->create();
        $ticket = ReturnTicket::factory()->create([
            'order_id' => $order->order_id,
        ]);

        $this->assertInstanceOf(ExternalOrderCache::class, $ticket->order);
        $this->assertEquals($order->order_id, $ticket->order->order_id);
    }

    public function test_it_belongs_to_created_by_user(): void
    {
        $user = User::factory()->create();
        $ticket = ReturnTicket::factory()->create([
            'created_by_user_id' => $user->user_id,
        ]);

        $this->assertInstanceOf(User::class, $ticket->createdBy);
        $this->assertEquals($user->user_id, $ticket->createdBy->user_id);
    }

    public function test_it_has_many_return_items(): void
    {
        $ticket = ReturnTicket::factory()->create();
        $item = ReturnItem::factory()->create([
            'ticket_id' => $ticket->ticket_id,
        ]);

        $this->assertTrue($ticket->returnItems->contains($item));
        $this->assertInstanceOf(ReturnItem::class, $ticket->returnItems->first());
    }

    public function test_it_has_many_evidences(): void
    {
        $ticket = ReturnTicket::factory()->create();
        $evidence = Evidence::factory()->create([
            'ticket_id' => $ticket->ticket_id,
        ]);

        $this->assertTrue($ticket->evidences->contains($evidence));
        $this->assertInstanceOf(Evidence::class, $ticket->evidences->first());
    }

    public function test_it_has_many_status_histories(): void
    {
        $ticket = ReturnTicket::factory()->create();
        $history = TicketStatusHistory::factory()->create([
            'ticket_id' => $ticket->ticket_id,
        ]);

        $this->assertTrue($ticket->statusHistory->contains($history));
        $this->assertInstanceOf(TicketStatusHistory::class, $ticket->statusHistory->first());
    }

    public function test_it_uses_soft_deletes(): void
    {
        $ticket = ReturnTicket::factory()->create();

        $ticket->delete();

        $this->assertSoftDeleted('return_tickets', [
            'ticket_id' => $ticket->ticket_id,
        ]);
    }
}
