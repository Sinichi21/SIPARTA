<?php

namespace App\Livewire\Users;

use App\Models\Personnel;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Edit extends Component
{
    public User $user;

    public string $name = '';
    public string $email = '';
    public ?int $personnel_id = null;
    public string $role = '';

    public function mount(User $user): void
    {
        Gate::authorize('users.update');
        abort_if(
            $user->hasRole('super-admin')
            && ! auth()->user()->hasRole('super-admin'),
            404
        );

        $this->user = $user->load('roles');
        $this->name = $user->name;
        $this->email = $user->email;
        $this->personnel_id = $user->personnel_id;
        $this->role = $user->getRoleNames()->first() ?? '';
    }

    public function save(AuditService $audit): void
    {
        Gate::authorize('users.update');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user->id),
            ],
            'personnel_id' => [
                'nullable',
                'integer',
                Rule::exists('personnels', 'id')
                    ->whereNull('deleted_at'),
            ],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')
                    ->where('guard_name', 'web'),
            ],
        ]);

        abort_if(
            $this->user->hasRole('super-admin')
            && ! auth()->user()->hasRole('super-admin'),
            403
        );

        abort_if(
            $data['role'] === 'super-admin'
            && ! auth()->user()->hasRole('super-admin'),
            403
        );

        if (
            $this->user->id === auth()->id()
            && $data['role'] !== $this->role
        ) {
            $this->addError(
                'role',
                'Role akun sendiri tidak dapat diubah dari halaman ini.'
            );

            return;
        }

        $old = $this->user->getOriginal();
        $emailChanged =
            Str::lower(trim($data['email']))
            !== Str::lower($this->user->email);

        $this->user->forceFill([
            'name' => trim($data['name']),
            'email' => Str::lower(trim($data['email'])),
            'personnel_id' => $data['personnel_id'],
            'email_verified_at' => $emailChanged
                ? null
                : $this->user->email_verified_at,
        ])->save();

        $this->user->syncRoles([$data['role']]);

        $audit->updated($this->user, $old);

        session()->flash(
            'success',
            'Pengguna berhasil diperbarui.'
        );
    }

    public function resendActivation(): void
    {
        Gate::authorize('users.update');

        abort_unless(
            $this->user->must_set_password,
            422,
            'Akun ini sudah menyelesaikan aktivasi.'
        );

        $status = Password::sendResetLink([
            'email' => $this->user->email,
        ]);

        abort_unless(
            $status === Password::RESET_LINK_SENT,
            422,
            __($status)
        );

        $this->user->forceFill([
            'activation_sent_at' => now(),
        ])->save();

        session()->flash(
            'success',
            'Tautan aktivasi berhasil dikirim ulang.'
        );
    }

    public function render()
    {
        return view('livewire.users.edit', [
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->when(
                    ! auth()->user()->hasRole('super-admin'),
                    fn ($query) => $query->where('name', '!=', 'super-admin')
                )
                ->orderBy('name')
                ->get(),

            'personnels' => Personnel::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'nip']),
        ]);
    }
}
