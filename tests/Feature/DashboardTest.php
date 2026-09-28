<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authorized_users_can_visit_the_dashboard(): void
    {
        Permission::firstOrCreate([
            'name' => 'dashboard.view',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();

        $user->givePermissionTo('dashboard.view');

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_users_without_dashboard_permission_are_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertForbidden();
    }
}