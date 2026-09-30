<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhaseTwelveOneV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_staff_role_can_be_limited_to_personal_permissions(): void
    {
        foreach ([
            'my-dashboard.view',
            'my-letters.view',
            'my-reports.view',
            'letters.view',
        ] as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $staff = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => 'web',
        ]);

        $staff->syncPermissions([
            'my-dashboard.view',
            'my-letters.view',
            'my-reports.view',
        ]);

        $this->assertTrue(
            $staff->hasPermissionTo(
                'my-dashboard.view'
            )
        );

        $this->assertFalse(
            $staff->hasPermissionTo(
                'letters.view'
            )
        );
    }

    public function test_non_super_admin_cannot_manage_super_admin_security(): void
    {
        $superRole = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $operatorRole = Role::firstOrCreate([
            'name' => 'operator',
            'guard_name' => 'web',
        ]);

        $developer = User::factory()->create();
        $developer->assignRole($superRole);

        $operator = User::factory()->create();
        $operator->assignRole($operatorRole);

        $this->actingAs($operator);

        $this->expectException(
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class
        );

        app(AccountSecurityService::class)
            ->revokeSessions($developer);
    }
}
