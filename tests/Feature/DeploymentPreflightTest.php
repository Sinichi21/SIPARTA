<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeploymentPreflightTest extends TestCase
{
    use RefreshDatabase;

    public function test_preflight_passes_in_normal_test_environment(): void
    {
        Storage::fake('local');

        $this->artisan('app:preflight')
            ->expectsOutputToContain('Deployment preflight passed.')
            ->assertExitCode(0);
    }

    public function test_strict_preflight_rejects_non_staging_safe_configuration(): void
    {
        Storage::fake('local');

        config([
            'app.debug' => true,
            'app.url' => 'http://localhost',
            'database.default' => 'sqlite',
            'session.secure' => false,
            'session.driver' => 'array',
            'queue.default' => 'sync',
        ]);

        $this->artisan('app:preflight', ['--strict' => true])
            ->expectsOutputToContain('Deployment preflight failed.')
            ->assertExitCode(1);
    }
}
