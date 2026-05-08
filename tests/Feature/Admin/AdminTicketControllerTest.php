<?php

namespace Tests\Feature\Admin;

use App\Models\ExternalOrderCache;
use App\Models\ReturnTicket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $support;
    private ReturnTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->support = User::factory()->create(['role' => 'support']);

        $order = ExternalOrderCache::factory()->create();
        $this->ticket = ReturnTicket::factory()->create([
            'order_id'       => $order->order_id,
            'current_status' => 'received',
        ]);
    }

    // ── Scenario 5: Acceso no autorizado ─────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/tickets');

        $response->assertRedirect('/login');
    }

    // ── Scenario 2 (parcial): Admin ve el listado ─────────────────────────────

    public function test_admin_can_view_tickets_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/tickets');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Tickets/Index'));
    }

    public function test_admin_can_filter_tickets_by_status(): void
    {
        // Ticket adicional con estado diferente
        $order2 = ExternalOrderCache::factory()->create();
        ReturnTicket::factory()->create([
            'order_id'       => $order2->order_id,
            'current_status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/tickets?status=received');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Tickets/Index')
            ->has('tickets.data', 1) // Solo el ticket 'received'
        );
    }

    // ── Scenario 3 (FR-03): Admin ve detalle ─────────────────────────────────

    public function test_admin_can_view_ticket_detail(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/tickets/{$this->ticket->ticket_id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Tickets/Show'));
    }

    // ── Scenario 2: Cambio de estado registra historial ──────────────────────

    public function test_admin_can_change_ticket_status_and_history_is_recorded(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch("/admin/tickets/{$this->ticket->ticket_id}/status", [
                'new_status' => 'under_review',
                'comment'    => null,
            ]);

        $response->assertRedirect(route('admin.tickets.show', $this->ticket->ticket_id));

        // El ticket cambió de estado
        $this->assertDatabaseHas('return_tickets', [
            'ticket_id'      => $this->ticket->ticket_id,
            'current_status' => 'under_review',
        ]);

        // El historial fue registrado
        $this->assertDatabaseHas('ticket_status_history', [
            'ticket_id'          => $this->ticket->ticket_id,
            'old_status'         => 'received',
            'new_status'         => 'under_review',
            'changed_by_user_id' => $this->admin->user_id,
        ]);
    }

    // ── Scenario 3: Support NO puede cerrar un ticket ─────────────────────────

    public function test_support_cannot_close_ticket(): void
    {
        $response = $this->actingAs($this->support)
            ->patch("/admin/tickets/{$this->ticket->ticket_id}/status", [
                'new_status' => 'closed',
                'comment'    => 'Cerrando',
            ]);

        $response->assertStatus(403);

        // El estado NO cambió
        $this->assertDatabaseHas('return_tickets', [
            'ticket_id'      => $this->ticket->ticket_id,
            'current_status' => 'received',
        ]);

        // No se registró historial
        $this->assertDatabaseCount('ticket_status_history', 0);
    }

    // ── Scenario 4: Rechazo requiere comentario ───────────────────────────────

    public function test_rejected_status_requires_comment(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch("/admin/tickets/{$this->ticket->ticket_id}/status", [
                'new_status' => 'rejected',
                // comment ausente intencionalmente
            ]);

        $response->assertSessionHasErrors(['comment']);

        // El estado NO cambió
        $this->assertDatabaseHas('return_tickets', [
            'ticket_id'      => $this->ticket->ticket_id,
            'current_status' => 'received',
        ]);
    }

    public function test_more_info_status_requires_comment(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch("/admin/tickets/{$this->ticket->ticket_id}/status", [
                'new_status' => 'more_information_requested',
                // comment ausente intencionalmente
            ]);

        $response->assertSessionHasErrors(['comment']);
    }

    // ── Scenario 6: Support SÍ puede acceder al panel (solo no puede cerrar) ──

    public function test_support_user_can_view_tickets_index(): void
    {
        $response = $this->actingAs($this->support)
            ->get('/admin/tickets');

        $response->assertStatus(200);
    }

    // ── Admin puede cerrar un ticket ──────────────────────────────────────────

    public function test_admin_can_close_ticket(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch("/admin/tickets/{$this->ticket->ticket_id}/status", [
                'new_status' => 'closed',
                'comment'    => 'Caso resuelto.',
            ]);

        $response->assertRedirect(route('admin.tickets.show', $this->ticket->ticket_id));

        $this->assertDatabaseHas('return_tickets', [
            'ticket_id'      => $this->ticket->ticket_id,
            'current_status' => 'closed',
        ]);

        $this->assertDatabaseHas('ticket_status_history', [
            'ticket_id'  => $this->ticket->ticket_id,
            'new_status' => 'closed',
            'comment'    => 'Caso resuelto.',
        ]);
    }
}
