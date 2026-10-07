<?php

namespace Tests\Feature\Admin;

use App\Events\TicketStatusUpdated;
use App\Listeners\SendTicketStatusNotification;
use App\Mail\TicketStatusUpdatedMail;
use App\Models\ExternalOrderCache;
use App\Models\ReturnTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ExternalOrderCache $order;
    private ReturnTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->admin = $admin;

        $this->order = ExternalOrderCache::factory()->create([
            'customer_email' => 'cliente.valid@tailoy.com.pe',
            'customer_full_name' => 'Carlos Mendoza',
        ]);

        $this->ticket = ReturnTicket::factory()->create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-NOTIF01',
            'current_status' => 'received',
        ]);
    }

    public function test_controller_status_update_dispatches_event_with_exact_payload_without_running_listener(): void
    {
        // Arrange: aislar evento para evitar ejecución del listener real
        Event::fake([TicketStatusUpdated::class]);

        // Act: actualización de estado desde el controlador administrativo
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.tickets.update-status', $this->ticket->ticket_id), [
                'new_status' => 'under_review',
                'comment' => 'Iniciando inspección del producto.',
            ]);

        // Assert: redirección y persistencia en BD
        $response->assertRedirect(route('admin.tickets.show', $this->ticket->ticket_id));
        $this->assertDatabaseHas('return_tickets', [
            'ticket_id' => $this->ticket->ticket_id,
            'current_status' => 'under_review',
        ]);

        // Verificar que el evento fue despachado con el payload exacto
        Event::assertDispatched(TicketStatusUpdated::class, function (TicketStatusUpdated $event) {
            return $event->ticket->ticket_id === $this->ticket->ticket_id
                && $event->comment === 'Iniciando inspección del producto.';
        });
    }

    public function test_listener_sends_email_to_valid_customer_recipient(): void
    {
        // Arrange: aislar capa de correo
        Mail::fake();

        $event = new TicketStatusUpdated($this->ticket, 'Su solicitud está en revisión.');
        $listener = new SendTicketStatusNotification();

        // Act: invocar directamente el listener
        $listener->handle($event);

        // Assert: verificar destinatario exacto y mailable enviado
        Mail::assertSent(TicketStatusUpdatedMail::class, function (TicketStatusUpdatedMail $mail) {
            return $mail->hasTo('cliente.valid@tailoy.com.pe')
                && $mail->comment === 'Su solicitud está en revisión.'
                && $mail->ticket->ticket_id === $this->ticket->ticket_id;
        });
    }

    public function test_listener_safely_skips_sending_and_logs_warning_when_customer_email_is_missing(): void
    {
        // Arrange
        Mail::fake();
        Log::spy();

        $orderNoEmail = ExternalOrderCache::factory()->create([
            'customer_email' => '',
        ]);
        $ticketNoEmail = ReturnTicket::factory()->create([
            'order_id' => $orderNoEmail->order_id,
            'tracking_code' => 'RET-NOEMAIL',
            'current_status' => 'received',
        ]);

        $event = new TicketStatusUpdated($ticketNoEmail, 'Actualización sin email');
        $listener = new SendTicketStatusNotification();

        // Act
        $listener->handle($event);

        // Assert: 0 correos enviados/encolados
        Mail::assertNothingSent();
        Mail::assertNothingQueued();

        // Log de advertencia registrado sin lanzar excepción
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message) use ($ticketNoEmail) {
                return str_contains($message, "No email found for customer in ticket {$ticketNoEmail->ticket_id}");
            });
    }

    public function test_listener_respects_mail_always_to_global_override_configuration(): void
    {
        // Arrange
        Mail::fake();
        Mail::alwaysTo('staging-override@tailoy.com.pe');

        $event = new TicketStatusUpdated($this->ticket, 'Prueba de destinatario forzado');
        $listener = new SendTicketStatusNotification();

        // Act
        $listener->handle($event);

        // Assert: MailFake registra el despacho del mailable bajo el override global
        Mail::assertSent(TicketStatusUpdatedMail::class, function (TicketStatusUpdatedMail $mail) {
            return $mail->comment === 'Prueba de destinatario forzado';
        });
    }

    public function test_mailable_renders_correct_subject_customer_name_and_status_label(): void
    {
        // Arrange
        $this->ticket->current_status = 'approved';
        $mail = new TicketStatusUpdatedMail($this->ticket, 'Devolución aprobada satisfactoriamente.');

        // Act
        $envelope = $mail->envelope();
        $renderedHtml = $mail->render();

        // Assert: Subject correcto con label formateado
        $this->assertSame(
            "Devoluciones Tai Loy — Actualización de Ticket {$this->ticket->tracking_code} [Aprobado]",
            $envelope->subject
        );

        // Assert: Contenido renderizado sin errores Blade
        $this->assertStringContainsString('Carlos Mendoza', $renderedHtml);
        $this->assertStringContainsString('Aprobado', $renderedHtml);
        $this->assertStringContainsString('Devolución aprobada satisfactoriamente.', $renderedHtml);
        $this->assertStringContainsString($this->ticket->tracking_code, $renderedHtml);
    }

    public function test_mailable_renders_cleanly_when_comment_is_null(): void
    {
        // Arrange: comentario nulo
        $this->ticket->current_status = 'under_review';
        $mail = new TicketStatusUpdatedMail($this->ticket, null);

        // Act
        $envelope = $mail->envelope();
        $renderedHtml = $mail->render();

        // Assert: Asunto y plantilla renderizan limpiamente sin caja de comentarios
        $this->assertSame(
            "Devoluciones Tai Loy — Actualización de Ticket {$this->ticket->tracking_code} [En Revisión]",
            $envelope->subject
        );
        $this->assertStringContainsString('Carlos Mendoza', $renderedHtml);
        $this->assertStringContainsString('En Revisión', $renderedHtml);
        $this->assertStringNotContainsString('Mensaje del equipo de soporte:', $renderedHtml);
    }

    #[DataProvider('domainStatusLabelProvider')]
    public function test_mailable_maps_all_domain_statuses_to_human_readable_labels(
        string $statusKey,
        string $expectedLabel,
    ): void {
        // Arrange
        $this->ticket->current_status = $statusKey;
        $mail = new TicketStatusUpdatedMail($this->ticket, 'Prueba de mapeo de estado');

        // Act
        $envelope = $mail->envelope();
        $renderedHtml = $mail->render();

        // Assert
        $this->assertStringContainsString("[{$expectedLabel}]", $envelope->subject);
        $this->assertStringContainsString($expectedLabel, $renderedHtml);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function domainStatusLabelProvider(): array
    {
        return [
            'status: received' => ['received', 'Recibido'],
            'status: under_review' => ['under_review', 'En Revisión'],
            'status: approved' => ['approved', 'Aprobado'],
            'status: rejected' => ['rejected', 'Rechazado'],
            'status: more_information_requested' => ['more_information_requested', 'Información Requerida'],
            'status: closed' => ['closed', 'Cerrado'],
        ];
    }
}
