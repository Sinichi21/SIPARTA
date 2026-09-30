<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_endpoint_is_available(): void
    {
        $this->getJson('/health/ready')
            ->assertOk()
            ->assertJson(['status' => 'ready']);
    }

    public function test_security_headers_are_added(): void
    {
        $middleware = new SecurityHeaders();

        $response = $middleware->handle(
            Request::create('/', 'GET'),
            fn () => new Response('OK')
        );

        $this->assertSame(
            'nosniff',
            $response->headers->get('X-Content-Type-Options')
        );

        $this->assertSame(
            'SAMEORIGIN',
            $response->headers->get('X-Frame-Options')
        );
    }
}
