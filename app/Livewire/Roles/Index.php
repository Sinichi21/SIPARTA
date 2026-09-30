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
        return view('livewire.roles.index', [
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->withCount(['permissions', 'users'])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
