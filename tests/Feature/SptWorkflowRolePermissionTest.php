<?php

namespace Tests\Feature;

use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\WorkflowRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SptWorkflowRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(WorkflowRolePermissionSeeder::class);
    }

    public function test_operator_can_prepare_and_submit_but_cannot_review_or_publish(): void
    {
        $role = Role::findByName('operator');

        $this->assertTrue($role->hasPermissionTo('letters.create'));
        $this->assertTrue($role->hasPermissionTo('letters.update'));
        $this->assertTrue($role->hasPermissionTo('letters.submit'));
        $this->assertFalse($role->hasPermissionTo('letters.verify'));
        $this->assertFalse($role->hasPermissionTo('letters.approve'));
        $this->assertFalse($role->hasPermissionTo('letters.publish'));
    }

    public function test_verifikator_can_verify_but_cannot_approve_or_publish(): void
    {
        $role = Role::findByName('verifikator');

        $this->assertTrue($role->hasPermissionTo('letters.view'));
        $this->assertTrue($role->hasPermissionTo('letters.verify'));
        $this->assertFalse($role->hasPermissionTo('letters.submit'));
        $this->assertFalse($role->hasPermissionTo('letters.approve'));
        $this->assertFalse($role->hasPermissionTo('letters.publish'));
    }

    public function test_pimpinan_can_approve_publish_and_cancel_but_not_edit_drafts(): void
    {
        $role = Role::findByName('pimpinan');

        $this->assertTrue($role->hasPermissionTo('letters.approve'));
        $this->assertTrue($role->hasPermissionTo('letters.publish'));
        $this->assertTrue($role->hasPermissionTo('letters.cancel'));
        $this->assertFalse($role->hasPermissionTo('letters.create'));
        $this->assertFalse($role->hasPermissionTo('letters.update'));
        $this->assertFalse($role->hasPermissionTo('letters.submit'));
    }

    public function test_admin_persuratan_can_manage_full_spt_workflow(): void
    {
        $role = Role::findByName('admin-persuratan');

        foreach ([
            'letters.create',
            'letters.update',
            'letters.submit',
            'letters.verify',
            'letters.approve',
            'letters.publish',
            'letters.cancel',
            'letters.import',
            'letters.export',
        ] as $permission) {
            $this->assertTrue(
                $role->hasPermissionTo($permission),
                "admin-persuratan missing {$permission}"
            );
        }
    }

    public function test_staff_remains_personal_portal_only(): void
    {
        $role = Role::findByName('staff');

        $this->assertTrue($role->hasPermissionTo('my-dashboard.view'));
        $this->assertTrue($role->hasPermissionTo('my-letters.view'));
        $this->assertTrue($role->hasPermissionTo('my-reports.view'));
        $this->assertFalse($role->hasPermissionTo('letters.view'));
        $this->assertFalse($role->hasPermissionTo('letters.submit'));
    }
}
