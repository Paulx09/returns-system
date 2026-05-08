<?php

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CustomerSessionMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', \App\Http\Middleware\CustomerSessionMiddleware::class])->group(function () {
            Route::get('/returns/protected', function () {
                return 'Protected Content';
            });
        });
    }

    public function test_blocks_access_without_customer_session(): void
    {
        $response = $this->get('/returns/protected');

        $response->assertRedirect('/returns/start');
    }

    public function test_allows_access_with_customer_session(): void
    {
        $response = $this->withSession(['customer_order_id' => 'dummy-uuid'])
            ->get('/returns/protected');

        $response->assertStatus(200);
        $response->assertSee('Protected Content');
    }
}
