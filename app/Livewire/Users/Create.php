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

class Create extends Component
{
    public string $name = '';
    public string $email = '';
    public ?int $personnel_id = null;
    public string $role = '';

    public function mount(): void
    {
        Gate::authorize('users.create');
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('users.create');

        $data = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
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
            $data['role'] === 'super-admin'
            && ! auth()->user()->hasRole('super-admin'),
            403
        );

        $user = User::unguarded(function () use ($data) {
            return User::create([
                'name' => trim($data['name']),
                'email' => Str::lower(trim($data['email'])),
                'personnel_id' => $data['personnel_id'],
                'password' => Str::random(80),
                'must_set_password' => true,
                'activation_sent_at' => now(),
                'invited_by' => auth()->id(),
            ]);
        });

        $user->syncRoles([$data['role']]);

        $audit->created($user);

        $status = Password::sendResetLink([
            'email' => $user->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            session()->flash(
                'warning',
                'Akun berhasil dibuat, tetapi email aktivasi belum terkirim. Gunakan tombol Kirim Ulang Aktivasi.'
            );
        } else {
            session()->flash(
                'success',
                'Akun berhasil dibuat. Pengguna menerima tautan untuk menentukan password sendiri.'
            );
        }

        return $this->redirectRoute(
            'users.index',
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.users.create', [
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
