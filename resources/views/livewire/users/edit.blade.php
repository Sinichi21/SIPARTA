<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('users.index') }}" wire:navigate class="text-sm text-blue-700">← Pengguna</a>
        <h1 class="mt-2 text-2xl font-bold">Edit Pengguna</h1>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="portal-card">
        <div class="mb-5 flex flex-wrap gap-2">
            @if($user->must_set_password)
                <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                    Menunggu Aktivasi
                </span>
            @endif

            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                2FA {{ $user->two_factor_confirmed_at ? 'Aktif' : 'Belum Aktif' }}
            </span>
        </div>

        <form wire:submit="save" class="space-y-6">
            @include('livewire.users._form')

            <div class="flex justify-end">
                <button type="submit" class="spt-action spt-action-primary">
                    Simpan
                </button>
            </div>
        </form>
    </section>

    @if($user->must_set_password)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="font-semibold text-amber-900">Aktivasi Belum Selesai</h2>
            <p class="mt-1 text-sm text-amber-800">
                Password belum pernah ditentukan oleh pengguna.
            </p>

            <button
                type="button"
                wire:click="resendActivation"
                wire:confirm="Kirim ulang tautan aktivasi ke {{ $user->email }}?"
                class="mt-4 rounded-xl bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white"
            >
                Kirim Ulang Aktivasi
            </button>
        </section>
    @endif
</div>
