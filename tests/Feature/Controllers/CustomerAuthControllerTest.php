<?php

namespace Tests\Feature\Controllers;

use App\Models\ExternalOrderCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_login_page(): void
    {
        $response = $this->get('/returns/start');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Returns/Start'));
    }

    public function test_customer_can_login_with_valid_recent_order(): void
    {
        $order = ExternalOrderCache::factory()->create([
            'order_number' => 'ORD-1234',
            'customer_dni' => '12345678',
            'order_date' => Carbon::now()->subDays(2),
        ]);

        $response = $this->post('/returns/login', [
            'order_number' => 'ORD-1234',
            'customer_dni' => '12345678',
        ]);

        $response->assertRedirect('/returns/dashboard');
        $response->assertSessionHas('customer_order_id', $order->order_id);
    }

    public function test_customer_cannot_login_with_invalid_credentials(): void
    {
        ExternalOrderCache::factory()->create([
            'order_number' => 'ORD-1234',
            'customer_dni' => '12345678',
            'order_date' => Carbon::now()->subDays(2),
        ]);

        $response = $this->post('/returns/login', [
            'order_number' => 'ORD-1234',
            'customer_dni' => '87654321', // Wrong DNI
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertNull(session('customer_order_id'));
    }

    public function test_customer_cannot_login_with_expired_order(): void
    {
        $order = ExternalOrderCache::factory()->create([
            'order_number' => 'ORD-1234',
            'customer_dni' => '12345678',
            'order_date' => Carbon::now()->subDays(8), // Exceeds 7 days
        ]);

        $response = $this->post('/returns/login', [
            'order_number' => 'ORD-1234',
            'customer_dni' => '12345678',
        ]);

        $response->assertSessionHasErrors(['login']);
        // "Plazo de devolución vencido"
        $this->assertEquals(
            'El plazo máximo de 7 días para devoluciones ha vencido para este pedido.',
            session('errors')->get('login')[0]
        );
        $this->assertNull(session('customer_order_id'));
    }
}
