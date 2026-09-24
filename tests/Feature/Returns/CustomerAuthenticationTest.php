<?php

namespace Tests\Feature\Returns;

use App\Models\ExternalOrderCache;
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
}