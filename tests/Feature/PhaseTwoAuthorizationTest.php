<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PhaseTwoAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'dashboard.view',
            'letters.view',
            'letters.create',
            'units.view',
            'units.manage',
            'audit-logs.view',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_user_with_dashboard_permission_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $user->givePermissionTo('dashboard.view');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_user_without_dashboard_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_user_without_unit_permission_cannot_access_units(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('units.index'))
            ->assertForbidden();
    }

    public function test_user_with_unit_permission_can_access_units(): void
    {
        $user = User::factory()->create();

        $user->givePermissionTo('units.view');

        $this->actingAs($user)
            ->get(route('units.index'))
            ->assertOk();
    }
}