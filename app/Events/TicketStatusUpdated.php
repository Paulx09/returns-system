<?php

namespace App\Events;

use App\Models\ReturnTicket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketStatusUpdated
{
    use Dispatchable, SerializesModels;

    public ReturnTicket $ticket;
    public ?string $comment;

    /**
     * Create a new event instance.
     */
    public function __construct(ReturnTicket $ticket, ?string $comment = null)
    {
        $this->ticket = $ticket;
        $this->comment = $comment;
    }
}
