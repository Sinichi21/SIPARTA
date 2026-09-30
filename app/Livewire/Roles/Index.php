<?php

namespace App\Livewire\Roles;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('roles.view');
    }

    public function render()
    {
        $isSuperAdmin =
            auth()->user()->hasRole(
                'super-admin'
            );

        return view(
            'livewire.roles.index',
            [
                'roles' => Role::query()
                    ->where(
                        'guard_name',
                        'web'
                    )
                    ->when(
                        ! $isSuperAdmin,
                        fn ($query) =>
                            $query->where(
                                'name',
                                '!=',
                                'super-admin'
                            )
                    )
                    ->withCount([
                        'permissions',
                        'users',
                    ])
                    ->orderBy('name')
                    ->get(),
            ]
        );
    }
}
