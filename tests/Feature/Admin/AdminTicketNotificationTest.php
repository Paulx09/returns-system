<?php

namespace Tests\Feature\Admin;

use App\Events\TicketStatusUpdated;
use App\Mail\TicketStatusUpdatedMail;
use App\Models\ExternalOrderCache;
use App\Models\ReturnTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTicketNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_ticket_status_dispatches_event(): void
    {
        Event::fake();

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $order = ExternalOrderCache::factory()->create([
            'customer_email' => 'cliente@ejemplo.com',
            'customer_full_name' => 'Juan Pérez',
        ]);
        $ticket = ReturnTicket::factory()->create([
            'order_id' => $order->order_id,
            'current_status' => 'received',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.tickets.update-status', $ticket->ticket_id), [
            'new_status' => 'under_review',
            'comment' => 'Revisando las evidencias enviadas.',
        ]);

        $response->assertRedirect(route('admin.tickets.show', $ticket->ticket_id));

        $this->assertDatabaseHas('return_tickets', [
            'ticket_id' => $ticket->ticket_id,
            'current_status' => 'under_review',
        ]);

        Event::assertDispatched(TicketStatusUpdated::class, function ($event) use ($ticket) {
            return $event->ticket->ticket_id === $ticket->ticket_id
                && $event->comment === 'Revisando las evidencias enviadas.';
        });
    }

    public function test_listener_sends_email_to_customer(): void
    {
        Mail::fake();

        $order = ExternalOrderCache::factory()->create([
            'customer_email' => 'cliente@ejemplo.com',
            'customer_full_name' => 'María López',
        ]);
        $ticket = ReturnTicket::factory()->create([
            'order_id' => $order->order_id,
            'current_status' => 'approved',
        ]);

        $event = new TicketStatusUpdated($ticket, 'Tu solicitud fue aprobada.');
        $listener = new \App\Listeners\SendTicketStatusNotification();
        $listener->handle($event);

        Mail::assertSent(TicketStatusUpdatedMail::class, function ($mail) {
            return $mail->hasTo('cliente@ejemplo.com')
                && $mail->comment === 'Tu solicitud fue aprobada.';
        });
    }
}
