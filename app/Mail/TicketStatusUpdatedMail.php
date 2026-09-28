<?php

namespace App\Mail;

use App\Models\ReturnTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ReturnTicket $ticket;
    public ?string $comment;

    /**
     * Map internal status to human-readable label in Spanish.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'received'                   => 'Recibido',
        'under_review'               => 'En Revisión',
        'approved'                   => 'Aprobado',
        'rejected'                   => 'Rechazado',
        'more_information_requested' => 'Información Requerida',
        'closed'                     => 'Cerrado',
    ];

    /**
     * Create a new message instance.
     */
    public function __construct(ReturnTicket $ticket, ?string $comment = null)
    {
        $this->ticket = $ticket;
        $this->comment = $comment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $statusLabel = self::STATUS_LABELS[$this->ticket->current_status] ?? $this->ticket->current_status;

        return new Envelope(
            subject: "Devoluciones Tai Loy — Actualización de Ticket {$this->ticket->tracking_code} [{$statusLabel}]",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $statusLabel = self::STATUS_LABELS[$this->ticket->current_status] ?? $this->ticket->current_status;

        return new Content(
            view: 'emails.ticket-status-updated',
            with: [
                'ticket'       => $this->ticket,
                'statusLabel'  => $statusLabel,
                'comment'      => $this->comment,
                'customerName' => $this->ticket->order->customer_full_name ?? 'Cliente',
            ],
        );
    }
}
