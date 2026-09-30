<?php

namespace App\Livewire\Roles;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Edit extends Component
{
    public Role $role;

    /** @var array<int, string> */
    public array $permissions = [];

    public function mount(Role $role): void
    {
        Gate::authorize('roles.manage');
        abort_if(
            $role->name === 'super-admin'
            && ! auth()->user()->hasRole('super-admin'),
            404
        );

        abort_if(
            $role->guard_name !== 'web',
            404
        );

        $this->role = $role;

        $this->permissions = $role
            ->permissions()
            ->pluck('name')
            ->all();
    }

    public function save(): void
    {
        Gate::authorize('roles.manage');

        abort_if(
            $this->role->name === 'super-admin',
            403,
            'Permission super-admin dikelola otomatis oleh sistem.'
        );

        $valid = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->pluck('name')
            ->all();

        $this->role->syncPermissions($valid);

        app(
            \Spatie\Permission\PermissionRegistrar::class
        )->forgetCachedPermissions();

        session()->flash(
            'success',
            'Permission role berhasil diperbarui.'
        );
    }

    public function render()
    {
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(
                fn (Permission $permission) =>
                    str($permission->name)
                        ->before('.')
                        ->toString()
            );

        return view('livewire.roles.edit', [
            'permissionGroups' => $permissions,
        ]);
    }
}
