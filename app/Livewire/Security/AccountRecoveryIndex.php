<?php

namespace App\Livewire\Security;

use App\Models\User;
use App\Services\AccountSecurityService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class AccountRecoveryIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $disableUserId = null;
    public string $disableReason = '';

    public function mount(): void
    {
        Gate::authorize(
            'users.security.manage'
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sendPasswordReset(
        int $userId,
        AccountSecurityService $security
    ): void {
        Gate::authorize(
            'users.security.manage'
        );

        $user = User::query()
            ->findOrFail($userId);

        $security->sendPasswordReset(
            $user
        );

        session()->flash(
            'success',
            'Tautan reset password telah dikirim. Password baru hanya ditentukan oleh pengguna.'
        );
    }

    public function resetTwoFactor(
        int $userId,
        AccountSecurityService $security
    ): void {
        Gate::authorize(
            'users.security.manage'
        );

        abort_if(
            $userId === auth()->id(),
            422,
            'Reset 2FA akun sendiri dilakukan dari Pengaturan Keamanan.'
        );

        $security->resetTwoFactor(
            User::query()
                ->findOrFail($userId)
        );

        session()->flash(
            'success',
            'Authenticator pengguna berhasil direset dan semua sesi aktif dicabut.'
        );
    }

    public function revokeSessions(
        int $userId,
        AccountSecurityService $security
    ): void {
        Gate::authorize(
            'users.security.manage'
        );

        abort_if(
            $userId === auth()->id(),
            422,
            'Gunakan logout untuk sesi akun sendiri.'
        );

        $count =
            $security->revokeSessions(
                User::query()
                    ->findOrFail($userId)
            );

        session()->flash(
            'success',
            "Sesi pengguna berhasil dicabut ({$count} sesi)."
        );
    }

    public function openDisable(
        int $userId
    ): void {
        Gate::authorize(
            'users.security.manage'
        );

        abort_if(
            $userId === auth()->id(),
            422,
            'Akun sendiri tidak dapat dinonaktifkan.'
        );

        $this->disableUserId =
            $userId;

        $this->disableReason = '';
    }

    public function disable(
        AccountSecurityService $security
    ): void {
        Gate::authorize(
            'users.security.manage'
        );

        $this->validate([
            'disableUserId' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'disableReason' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],
        ]);

        $security->disable(
            User::query()
                ->findOrFail(
                    $this->disableUserId
                ),
            auth()->id(),
            $this->disableReason
        );

        $this->disableUserId = null;
        $this->disableReason = '';

        session()->flash(
            'success',
            'Akun berhasil dinonaktifkan dan seluruh sesi pengguna dicabut.'
        );
    }

    public function enable(
        int $userId,
        AccountSecurityService $security
    ): void {
        Gate::authorize(
            'users.security.manage'
        );

        $security->enable(
            User::query()
                ->findOrFail($userId)
        );

        session()->flash(
            'success',
            'Akun berhasil diaktifkan kembali.'
        );
    }

    public function render()
    {
        return view(
            'livewire.security.account-recovery-index',
            [
                'users' => User::query()
                    ->when(
                        ! auth()->user()->hasRole('super-admin'),
                        fn ($query) => $query->whereDoesntHave(
                            'roles',
                            fn ($roleQuery) => $roleQuery->where(
                                'name',
                                'super-admin'
                            )
                        )
                    )
                    ->when(
                        filled($this->search),
                        function ($query) {
                            $search =
                                trim($this->search);

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
                    ->orderBy('name')
                    ->paginate(15),
            ]
        );
    }
}
