<?php

namespace Tests\Feature\Returns;

use App\Models\ExternalOrderCache;
use App\Models\ReturnTicket;
use Database\Seeders\ExternalOrderCacheSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_start_a_return_with_a_recent_order(): void
    {
        // Arrange
        $order = ExternalOrderCache::factory()->create([
            'order_number' => 'ORD-RECENT',
            'customer_dni' => '12345678',
            'order_date' => Carbon::now()->subDays(6),
        ]);

        // Act
        $response = $this->post(route('returns.login'), [
            'order_number' => 'ORD-RECENT',
            'customer_dni' => '12345678',
        ]);

        // Assert
        $response->assertRedirect(route('returns.dashboard'));
        $response->assertSessionHas('customer_order_id', $order->order_id);
    }

    public function test_customer_cannot_start_a_return_with_unknown_order_data(): void
    {
        // Arrange
        ExternalOrderCache::factory()->create([
            'order_number' => 'ORD-KNOWN',
            'customer_dni' => '12345678',
        ]);

        // Act
        $response = $this->post(route('returns.login'), [
            'order_number' => 'ORD-UNKNOWN',
            'customer_dni' => '12345678',
        ]);

        // Assert
        $response->assertSessionHasErrors(['login']);
        $this->assertFalse(session()->has('customer_order_id'));
    }

    public function test_customer_cannot_start_a_return_after_the_warranty_period(): void
    {
        // Arrange
        ExternalOrderCache::factory()->create([
            'order_number' => 'ORD-EXPIRED',
            'customer_dni' => '12345678',
            'order_date' => Carbon::now()->subDays(8),
        ]);

        // Act
        $response = $this->post(route('returns.login'), [
            'order_number' => 'ORD-EXPIRED',
            'customer_dni' => '12345678',
        ]);

        // Assert
        $response->assertSessionHasErrors(['login']);
        $this->assertSame(
            'El plazo máximo de 7 días para devoluciones ha vencido para este pedido.',
            session('errors')->get('login')[0]
        );
    }

    public function test_customer_dni_must_be_strictly_eight_numeric_digits(): void
    {
        // Arrange
        ExternalOrderCache::factory()->create([
            'order_number' => 'ORD-123456',
            'customer_dni' => '12345678',
            'order_date' => Carbon::now()->subDays(2),
        ]);

        // Act: DNI alfanumérico / menos de 8 dígitos (TC-CLI-04)
        $response = $this->post(route('returns.login'), [
            'order_number' => 'ORD-123456',
            'customer_dni' => '1234A',
        ]);

        // Assert
        $response->assertSessionHasErrors(['customer_dni']);
        $this->assertSame(
            'El DNI debe contener exactamente 8 dígitos numéricos.',
            session('errors')->get('customer_dni')[0]
        );
        $this->assertFalse(session()->has('customer_order_id'));
    }

    public function test_order_with_active_ticket_redirects_to_tracking_with_info_flash(): void
    {
        // Arrange: ejecutar seeder canónico que incluye ORD-ACTIVE-01 (TC-CLI-03)
        $this->seed(ExternalOrderCacheSeeder::class);

        $activeOrder = ExternalOrderCache::where('order_number', 'ORD-ACTIVE-01')->firstOrFail();
        $activeTicket = ReturnTicket::where('order_id', $activeOrder->order_id)->firstOrFail();
        $this->assertSame('received', $activeTicket->current_status);

        // Act
        $response = $this->post(route('returns.login'), [
            'order_number' => 'ORD-ACTIVE-01',
            'customer_dni' => '12345678',
        ]);

        // Assert
        $response->assertRedirect(route('returns.tracking'));
        $response->assertSessionHas('customer_order_id', $activeOrder->order_id);
        $response->assertSessionHas('has_active_ticket', true);
        $response->assertSessionHas('info', 'Este pedido ya cuenta con una solicitud de devolución en trámite.');
    }
}