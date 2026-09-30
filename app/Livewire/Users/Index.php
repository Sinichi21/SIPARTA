<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'all';

    public function mount(): void
    {
        Gate::authorize('users.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $isSuperAdmin =
            auth()->user()->hasRole(
                'super-admin'
            );

        return view(
            'livewire.users.index',
            [
                'users' => User::query()
                    ->with([
                        'roles',
                        'personnel',
                    ])
                    ->when(
                        ! $isSuperAdmin,
                        fn ($query) =>
                            $query->whereDoesntHave(
                                'roles',
                                fn ($roleQuery) =>
                                    $roleQuery->where(
                                        'name',
                                        'super-admin'
                                    )
                            )
                    )
                    ->when(
                        filled($this->search),
                        function ($query) {
                            $search =
                                trim(
                                    $this->search
                                );

                            $query->where(
                                fn ($query) =>
                                    $query
                                        ->where(
                                            'name',
                                            'ilike',
                                            "%{$search}%"
                                        )
                                        ->orWhere(
                                            'email',
                                            'ilike',
                                            "%{$search}%"
                                        )
                            );
                        }
                    )
                    ->when(
                        $this->status
                        === 'active',
                        fn ($query) =>
                            $query->whereNull(
                                'account_disabled_at'
                            )
                    )
                    ->when(
                        $this->status
                        === 'disabled',
                        fn ($query) =>
                            $query->whereNotNull(
                                'account_disabled_at'
                            )
                    )
                    ->when(
                        $this->status
                        === 'pending',
                        fn ($query) =>
                            $query->where(
                                'must_set_password',
                                true
                            )
                    )
                    ->orderBy('name')
                    ->paginate(15),
            ]
        );
    }
}
