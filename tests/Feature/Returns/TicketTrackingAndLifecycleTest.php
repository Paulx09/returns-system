<?php

namespace Tests\Feature\Returns;

use App\Models\Evidence;
use App\Models\ExternalOrderCache;
use App\Models\OrderItem;
use App\Models\ReturnItem;
use App\Models\ReturnReason;
use App\Models\ReturnTicket;
use App\Models\TicketStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketTrackingAndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private ExternalOrderCache $order;
    private OrderItem $orderItem;
    private ReturnReason $reason;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->order = ExternalOrderCache::factory()->create([
            'order_number' => 'ORD-TRACK-100',
            'customer_dni' => '44556677',
            'order_date' => Carbon::now()->subDays(3),
        ]);

        $this->orderItem = OrderItem::factory()->create([
            'order_id' => $this->order->order_id,
            'product_name' => 'Goma en barra Pritt',
            'quantity' => 2,
        ]);

        $this->reason = ReturnReason::create([
            'name' => 'DEFECTIVE',
            'description' => 'Defecto de fábrica',
        ]);
    }

    public function test_customer_with_active_ticket_is_redirected_to_tracking_on_login(): void
    {
        // Arrange: un ticket activo para la orden
        $ticket = ReturnTicket::create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-ACTIVE01',
            'current_status' => 'under_review',
        ]);

        // Act: cliente ingresa con pedido y DNI
        $response = $this->post(route('returns.login'), [
            'order_number' => 'ORD-TRACK-100',
            'customer_dni' => '44556677',
        ]);

        // Assert: redirige a tracking en vez de dashboard
        $response->assertRedirect(route('returns.tracking'));
        $this->assertTrue(session('has_active_ticket'));
        $this->assertSame($this->order->order_id, session('customer_order_id'));
    }

    public function test_customer_can_track_ticket_by_tracking_code_and_dni(): void
    {
        // Arrange
        $ticket = ReturnTicket::create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-DIRECT01',
            'current_status' => 'received',
        ]);

        // Act
        $response = $this->post(route('returns.track-by-code'), [
            'tracking_code' => 'RET-DIRECT01',
            'customer_dni' => '44556677',
        ]);

        // Assert
        $response->assertRedirect(route('returns.tracking'));
        $this->assertSame($this->order->order_id, session('customer_order_id'));
        $this->assertTrue(session('has_active_ticket'));
    }

    public function test_track_by_code_rejects_mismatched_dni_or_unknown_code(): void
    {
        // Arrange
        ReturnTicket::create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-SECRET01',
            'current_status' => 'received',
        ]);

        // Act: DNI equivocado
        $response = $this->post(route('returns.track-by-code'), [
            'tracking_code' => 'RET-SECRET01',
            'customer_dni' => '99999999',
        ]);

        // Assert
        $response->assertSessionHasErrors(['tracking']);
        $this->assertNull(session('customer_order_id'));
    }

    public function test_customer_with_active_ticket_is_redirected_from_dashboard_to_tracking(): void
    {
        // Arrange: cliente autenticado con ticket activo en sesión
        $response = $this->withSession([
            'customer_order_id' => $this->order->order_id,
            'has_active_ticket' => true,
        ])->get(route('returns.dashboard'));

        // Assert
        $response->assertRedirect(route('returns.tracking'));
    }

    public function test_customer_cannot_submit_duplicate_ticket_while_ticket_is_active(): void
    {
        // Arrange: ya existe un ticket no cerrado en BD
        $existingTicket = ReturnTicket::create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-EXISTING',
            'current_status' => 'received',
        ]);

        $file = UploadedFile::fake()->image('foto.jpg');

        // Act: intento de POST /returns/tickets para la misma orden
        $response = $this->withSession([
            'customer_order_id' => $this->order->order_id,
        ])->post(route('returns.tickets.store'), [
            'items' => [
                [
                    'order_item_id' => $this->orderItem->order_item_id,
                    'return_reason_id' => $this->reason->reason_id,
                    'quantity' => 1,
                    'condition' => 'damaged',
                ],
            ],
            'customer_notes' => 'Intento duplicado',
            'evidences' => [$file],
        ]);

        // Assert: redirige a tracking con flash error y no crea un segundo ticket
        $response->assertRedirect(route('returns.tracking'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('return_tickets', 1);
    }

    public function test_customer_can_create_new_ticket_if_previous_ticket_was_closed(): void
    {
        // Arrange: ticket anterior finalizado (closed)
        ReturnTicket::create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-OLDCLOSED',
            'current_status' => 'closed',
        ]);

        $file = UploadedFile::fake()->image('foto_nueva.jpg');

        // Act: cliente crea una nueva solicitud dentro del plazo de 7 días
        $response = $this->withSession([
            'customer_order_id' => $this->order->order_id,
            'has_active_ticket' => false,
        ])->post(route('returns.tickets.store'), [
            'items' => [
                [
                    'order_item_id' => $this->orderItem->order_item_id,
                    'return_reason_id' => $this->reason->reason_id,
                    'quantity' => 1,
                    'condition' => 'damaged',
                ],
            ],
            'customer_notes' => 'Segunda solicitud permitida porque la primera se cerró',
            'evidences' => [$file],
        ]);

        // Assert: ticket nuevo creado exitosamente
        $response->assertRedirect(route('returns.success'));
        $this->assertDatabaseCount('return_tickets', 2);
    }

    public function test_customer_can_view_tracking_page_with_ticket_details(): void
    {
        // Arrange: ticket con items, evidencias e historial
        $ticket = ReturnTicket::create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-VIEW01',
            'current_status' => 'more_information_requested',
            'customer_comment' => 'Producto llegó roto',
        ]);

        ReturnItem::create([
            'ticket_id' => $ticket->ticket_id,
            'order_item_id' => $this->orderItem->order_item_id,
            'reason_id' => $this->reason->reason_id,
            'quantity_to_return' => 1,
        ]);

        Evidence::create([
            'ticket_id' => $ticket->ticket_id,
            'file_path' => 'evidences/fake.jpg',
            'file_name' => 'fake.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
        ]);

        TicketStatusHistory::create([
            'ticket_id' => $ticket->ticket_id,
            'old_status' => 'received',
            'new_status' => 'more_information_requested',
            'comment' => 'Por favor envía foto del empaque exterior.',
        ]);

        // Act
        $response = $this->withSession([
            'customer_order_id' => $this->order->order_id,
        ])->get(route('returns.tracking'));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Returns/Tracking')
            ->has('ticket')
            ->where('ticket.tracking_code', 'RET-VIEW01')
            ->where('ticket.current_status', 'more_information_requested')
            ->has('ticket.return_items', 1)
            ->has('ticket.evidences', 1)
            ->has('ticket.status_history', 1)
            ->has('order')
        );
    }
}
