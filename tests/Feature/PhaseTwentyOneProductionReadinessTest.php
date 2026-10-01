<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PhaseTwentyOneProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_endpoint_is_available_and_rate_limited(): void
    {
        $this->get(route('health.ready'))
            ->assertOk()
            ->assertJson([
                'status' => 'ready',
            ]);

        $route = collect(Route::getRoutes())
            ->first(fn ($route) => $route->getName() === 'health.ready');

        $this->assertNotNull($route);

        $this->assertContains(
            'throttle:30,1',
            $route->gatherMiddleware()
        );
    }

    public function test_production_environment_template_has_safe_baseline(): void
    {
        $contents = file_get_contents(
            base_path('.env.production.example')
        );

        $this->assertIsString($contents);
        $this->assertStringContainsString('APP_ENV=production', $contents);
        $this->assertStringContainsString('APP_DEBUG=false', $contents);
        $this->assertStringContainsString('APP_TIMEZONE=Asia/Makassar', $contents);
        $this->assertStringContainsString('DB_CONNECTION=pgsql', $contents);
        $this->assertStringContainsString('SESSION_ENCRYPT=true', $contents);
        $this->assertStringContainsString('LOG_CHANNEL=daily', $contents);
        $this->assertStringNotContainsString('DB_PASSWORD=password', $contents);
    }
}
