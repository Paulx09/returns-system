<?php

namespace App\Listeners;

use App\Events\TicketStatusUpdated;
use App\Mail\TicketStatusUpdatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTicketStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TicketStatusUpdated $event): void
    {
        $ticket = $event->ticket;
        $ticket->loadMissing('order');

        $customerEmail = $ticket->order->customer_email ?? null;

        if (app()->environment('local') && env('MAIL_TEST_RECIPIENT')) {
            $customerEmail = env('MAIL_TEST_RECIPIENT');
        }

        if (!$customerEmail) {
            Log::warning("No email found for customer in ticket {$ticket->ticket_id}. Notification skipped.");
            return;
        }

        Mail::to($customerEmail)->send(new TicketStatusUpdatedMail($ticket, $event->comment));
    }
}
