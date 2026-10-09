<?php

namespace Tests\Feature\Admin;

use App\Models\ExternalOrderCache;
use App\Models\ReturnTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminTicketLifecycleTransitionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $support;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->support = User::factory()->create(['role' => 'support']);
        $this->customer = User::factory()->make(['role' => 'customer']);
    }

    /**
     * Helper to create a ticket with a specific initial status.
     */
    private function createTicketWithStatus(string $status = 'received'): ReturnTicket
    {
        $order = ExternalOrderCache::factory()->create();

        return ReturnTicket::factory()->create([
            'order_id'       => $order->order_id,
            'current_status' => $status,
        ]);
    }

    #[DataProvider('validTransitionsProvider')]
    public function test_valid_state_transitions_succeed_and_record_history_invariants(
        string $fromStatus,
        string $toStatus,
        ?string $comment,
    ): void {
        $ticket = $this->createTicketWithStatus($fromStatus);

        $response = $this->actingAs($this->admin)
            ->patch("/admin/tickets/{$ticket->ticket_id}/status", [
                'new_status' => $toStatus,
                'comment'    => $comment,
            ]);

        $response->assertRedirect(route('admin.tickets.show', $ticket->ticket_id));

        // Invariante 1: return_tickets.current_status actualizado
        $this->assertDatabaseHas('return_tickets', [
            'ticket_id'      => $ticket->ticket_id,
            'current_status' => $toStatus,
        ]);

        // Invariante 2: ticket_status_history inserción exacta
        $this->assertDatabaseHas('ticket_status_history', [
            'ticket_id'          => $ticket->ticket_id,
            'old_status'         => $fromStatus,
            'new_status'         => $toStatus,
            'comment'            => $comment,
            'changed_by_user_id' => $this->admin->user_id,
        ]);
    }

    /**
     * Matriz formal de transiciones válidas según reglas de negocio.
     *
     * @return array<string, array{string, string, ?string}>
     */
    public static function validTransitionsProvider(): array
    {
        return [
            'received -> under_review' => ['received', 'under_review', null],
            'received -> rejected (con motivo)' => ['received', 'rejected', 'Documentación incompleta.'],
            'under_review -> more_information_requested' => ['under_review', 'more_information_requested', 'Adjuntar foto del producto.'],
            'under_review -> approved' => ['under_review', 'approved', 'Producto verificado correctamente.'],
            'under_review -> rejected' => ['under_review', 'rejected', 'Producto presenta daño por mal uso.'],
            'more_information_requested -> under_review' => ['more_information_requested', 'under_review', 'Cliente adjuntó comprobante.'],
            'more_information_requested -> closed (por abandono)' => ['more_information_requested', 'closed', 'Cierre por falta de respuesta.'],
            'approved -> closed' => ['approved', 'closed', 'Nota de crédito emitida y procesada.'],
        ];
    }

    #[DataProvider('invalidTransitionsProvider')]
    public function test_illegal_state_transitions_fail_validation_and_preserve_state(
        string $fromStatus,
        string $toStatus,
    ): void {
        $ticket = $this->createTicketWithStatus($fromStatus);

        $response = $this->actingAs($this->admin)
            ->patchJson("/admin/tickets/{$ticket->ticket_id}/status", [
                'new_status' => $toStatus,
                'comment'    => 'Intento de transición ilegal.',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['new_status']);

        // Invariante 1: El estado del ticket permanece inalterado
        $this->assertSame($fromStatus, $ticket->fresh()->current_status);

        // Invariante 2: 0 registros en el historial
        $this->assertDatabaseMissing('ticket_status_history', [
            'ticket_id' => $ticket->ticket_id,
        ]);
    }

    /**
     * Matriz de transiciones prohibidas / no permitidas.
     *
     * @return array<string, array{string, string}>
     */
    public static function invalidTransitionsProvider(): array
    {
        return [
            'received -> approved (salta revisión)' => ['received', 'approved'],
            'received -> closed (salta revisión/aprobación)' => ['received', 'closed'],
            'received -> more_information_requested' => ['received', 'more_information_requested'],
            'under_review -> closed (sin aprobar previo)' => ['under_review', 'closed'],
            'under_review -> received (retroceso inválido)' => ['under_review', 'received'],
            'approved -> under_review' => ['approved', 'under_review'],
            'approved -> rejected' => ['approved', 'rejected'],
            'approved -> more_information_requested' => ['approved', 'more_information_requested'],

            // Estados Terminales: Prohibido salir de 'rejected'
            'rejected -> received (terminal)' => ['rejected', 'received'],
            'rejected -> under_review (terminal)' => ['rejected', 'under_review'],
            'rejected -> approved (terminal)' => ['rejected', 'approved'],
            'rejected -> closed (terminal)' => ['rejected', 'closed'],

            // Estados Terminales: Prohibido salir de 'closed'
            'closed -> received (terminal)' => ['closed', 'received'],
            'closed -> under_review (terminal)' => ['closed', 'under_review'],
            'closed -> approved (terminal)' => ['closed', 'approved'],
            'closed -> rejected (terminal)' => ['closed', 'rejected'],
        ];
    }

    public function test_transition_to_more_information_requested_without_comment_returns_422(): void
    {
        $ticket = $this->createTicketWithStatus('under_review');

        $response = $this->actingAs($this->admin)
            ->patchJson("/admin/tickets/{$ticket->ticket_id}/status", [
                'new_status' => 'more_information_requested',
                'comment'    => '',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['comment']);

        $this->assertSame('under_review', $ticket->fresh()->current_status);
        $this->assertDatabaseMissing('ticket_status_history', [
            'ticket_id' => $ticket->ticket_id,
        ]);
    }

    public function test_transition_to_rejected_without_comment_returns_422(): void
    {
        $ticket = $this->createTicketWithStatus('under_review');

        $response = $this->actingAs($this->admin)
            ->patchJson("/admin/tickets/{$ticket->ticket_id}/status", [
                'new_status' => 'rejected',
                // comment omitido
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['comment']);

        $this->assertSame('under_review', $ticket->fresh()->current_status);
        $this->assertDatabaseMissing('ticket_status_history', [
            'ticket_id' => $ticket->ticket_id,
        ]);
    }

    public function test_transition_to_under_review_without_comment_is_allowed(): void
    {
        $ticket = $this->createTicketWithStatus('received');

        $response = $this->actingAs($this->admin)
            ->patch("/admin/tickets/{$ticket->ticket_id}/status", [
                'new_status' => 'under_review',
                'comment'    => null,
            ]);

        $response->assertRedirect(route('admin.tickets.show', $ticket->ticket_id));
        $this->assertSame('under_review', $ticket->fresh()->current_status);

        $this->assertDatabaseHas('ticket_status_history', [
            'ticket_id'  => $ticket->ticket_id,
            'old_status' => 'received',
            'new_status' => 'under_review',
            'comment'    => null,
        ]);
    }

    public function test_unauthenticated_user_cannot_transition_ticket_status(): void
    {
        $ticket = $this->createTicketWithStatus('received');

        $response = $this->patch("/admin/tickets/{$ticket->ticket_id}/status", [
            'new_status' => 'under_review',
        ]);

        $response->assertRedirect('/login');
        $this->assertSame('received', $ticket->fresh()->current_status);
        $this->assertDatabaseMissing('ticket_status_history', [
            'ticket_id' => $ticket->ticket_id,
        ]);
    }

    public function test_regular_customer_cannot_transition_ticket_status(): void
    {
        $ticket = $this->createTicketWithStatus('received');

        $response = $this->actingAs($this->customer)
            ->patch("/admin/tickets/{$ticket->ticket_id}/status", [
                'new_status' => 'under_review',
            ]);

        $response->assertStatus(403);
        $this->assertSame('received', $ticket->fresh()->current_status);
        $this->assertDatabaseMissing('ticket_status_history', [
            'ticket_id' => $ticket->ticket_id,
        ]);
    }

    public function test_support_user_can_transition_to_under_review_but_not_to_closed(): void
    {
        $ticket = $this->createTicketWithStatus('approved');

        // Intento de cerrar ticket por usuario support -> 403 Forbidden
        $response = $this->actingAs($this->support)
            ->patch("/admin/tickets/{$ticket->ticket_id}/status", [
                'new_status' => 'closed',
                'comment'    => 'Intento de cierre.',
            ]);

        $response->assertStatus(403);
        $this->assertSame('approved', $ticket->fresh()->current_status);
        $this->assertDatabaseMissing('ticket_status_history', [
            'ticket_id' => $ticket->ticket_id,
        ]);
    }
}
