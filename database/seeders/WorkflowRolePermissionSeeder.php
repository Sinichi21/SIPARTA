<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class WorkflowRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $defaults = [
            'admin-persuratan' => [
                'dashboard.view',
                'incoming-letters.view','incoming-letters.create','incoming-letters.update','incoming-letters.process','incoming-letters.archive',
                'outgoing-letters.view','outgoing-letters.create','outgoing-letters.update','outgoing-letters.verify','outgoing-letters.approve','outgoing-letters.number','outgoing-letters.publish','outgoing-letters.send','outgoing-letters.archive',
                'issued-letters.view','issued-letters.revoke',
                'letters.view','letters.create','letters.update','letters.submit','letters.verify','letters.approve','letters.publish','letters.cancel','letters.archive','letters.export','letters.import',
                'personnels.view','personnels.create','personnels.update','personnels.deactivate','personnels.merge',
                'units.view','activity-types.view','letter-types.view',
                'reports.view','reports.export','audit-logs.view',
            ],
            'operator' => [
                'dashboard.view',
                'letters.view','letters.create','letters.update','letters.submit','letters.export','letters.import',
                'outgoing-letters.view','outgoing-letters.create','outgoing-letters.update',
                'personnels.view','units.view','activity-types.view','letter-types.view',
                'reports.view','reports.export',
            ],
            'verifikator' => [
                'dashboard.view',
                'letters.view','letters.verify',
                'outgoing-letters.view','outgoing-letters.verify',
                'personnels.view','reports.view',
            ],
            'pimpinan' => [
                'dashboard.view',
                'letters.view','letters.approve','letters.publish','letters.cancel',
                'outgoing-letters.view','outgoing-letters.approve','outgoing-letters.publish',
                'issued-letters.view','issued-letters.revoke',
                'reports.view','audit-logs.view',
            ],
            'viewer' => [
                'dashboard.view',
                'letters.view','incoming-letters.view','outgoing-letters.view',
                'issued-letters.view','personnels.view','units.view',
                'activity-types.view','letter-types.view','reports.view',
            ],
            'staff' => [
                'my-dashboard.view','my-letters.view','my-reports.view',
            ],
        ];

        foreach ($defaults as $roleName => $permissions) {
            Role::findByName($roleName, $guard)->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
