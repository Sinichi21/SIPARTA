<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        $permissions = [
            'dashboard.view',

            'letters.view',
            'letters.create',
            'letters.update',
            'letters.submit',
            'letters.verify',
            'letters.approve',
            'letters.publish',
            'letters.cancel',
            'letters.archive',
            'letters.export',
            'letters.import',

            'personnels.view',
            'personnels.create',
            'personnels.update',
            'personnels.deactivate',
            'personnels.merge',

            'units.view',
            'units.manage',

            'activity-types.view',
            'activity-types.manage',

            'letter-types.view',
            'letter-types.manage',

            'reports.view',
            'reports.export',

            'users.view',
            'users.create',
            'users.update',
            'users.deactivate',
            'users.security.manage',

            'roles.view',
            'roles.manage',

            'audit-logs.view',

            'settings.view',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        $roles = [
            'super-admin',
            'admin-persuratan',
            'operator',
            'verifikator',
            'pimpinan',
            'viewer',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);
        }

        $superAdmin = Role::findByName('super-admin', $guard);
        $superAdmin->syncPermissions(
            Permission::where('guard_name', $guard)->get()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}