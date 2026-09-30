<div class="portal-page">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">
            Keamanan & Pemulihan Akun
        </h1>

        <p class="mt-1 max-w-3xl text-sm text-slate-500">
            Administrator tidak pernah menentukan atau melihat password baru pengguna.
            Reset password hanya mengirim tautan aman agar pengguna membuat password sendiri.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @error('security')
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ $message }}
        </div>
    @enderror

    <section class="rounded-2xl border border-blue-100 bg-blue-50/60 p-5">
        <h2 class="font-semibold text-blue-950">
            Strategi autentikasi SIPARTA
        </h2>

        <div class="mt-3 grid gap-3 text-sm text-blue-900 md:grid-cols-3">
            <div class="rounded-xl bg-white/70 p-4">
                <strong>Password</strong>
                <p class="mt-1 text-xs">Hanya diketahui pengguna.</p>
            </div>

            <div class="rounded-xl bg-white/70 p-4">
                <strong>Authenticator (TOTP)</strong>
                <p class="mt-1 text-xs">Digunakan untuk kode 2FA tanpa email.</p>
            </div>

            <div class="rounded-xl bg-white/70 p-4">
                <strong>Email penting</strong>
                <p class="mt-1 text-xs">Reset password, aktivasi, dan insiden keamanan.</p>
            </div>
        </div>
    </section>

    <div class="portal-card">
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Cari nama atau email pengguna..."
            class="w-full rounded-xl border-slate-200 text-sm"
        >
    </div>

    <div class="portal-card portal-table-card">
        <div class="overflow-x-auto">
            <table class="portal-table">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Pengguna</th>
                        <th class="px-5 py-3">2FA</th>
                        <th class="px-5 py-3">Login Terakhir</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Tindakan</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @foreach($users as $user)
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="block text-slate-900">
                                    {{ $user->name }}
                                </strong>
                                <span class="text-xs text-slate-500">
                                    {{ $user->email }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                @if($user->two_factor_confirmed_at)
                                    <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                                        Belum aktif
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-xs text-slate-600">
                                <span class="block">
                                    {{ $user->last_login_at?->translatedFormat('d M Y H:i') ?: 'Belum tercatat' }}
                                </span>
                                <span class="mt-1 block text-slate-400">
                                    {{ $user->last_login_ip ?: '-' }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                @if($user->account_disabled_at)
                                    <span class="rounded-full bg-red-50 px-2 py-1 text-xs font-semibold text-red-700">
                                        Nonaktif
                                    </span>
                                @else
                                    <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">
                                        Aktif
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="sendPasswordReset({{ $user->id }})"
                                        wire:confirm="Kirim tautan reset password ke {{ $user->email }} dan cabut sesi aktif pengguna?"
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        Kirim Reset Password
                                    </button>

                                    @if($user->id !== auth()->id())
                                        @if($user->two_factor_confirmed_at)
                                            <button
                                                type="button"
                                                wire:click="resetTwoFactor({{ $user->id }})"
                                                wire:confirm="Reset Authenticator pengguna ini? Semua sesi aktif juga akan dicabut."
                                                class="rounded-lg border border-amber-200 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50"
                                            >
                                                Reset 2FA
                                            </button>
                                        @endif

                                        <button
                                            type="button"
                                            wire:click="revokeSessions({{ $user->id }})"
                                            wire:confirm="Cabut seluruh sesi aktif pengguna?"
                                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Cabut Sesi
                                        </button>

                                        @if($user->account_disabled_at)
                                            <button
                                                type="button"
                                                wire:click="enable({{ $user->id }})"
                                                class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"
                                            >
                                                Aktifkan
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="openDisable({{ $user->id }})"
                                                class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50"
                                            >
                                                Nonaktifkan
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{ $users->links() }}

    @if($disableUserId)
        <section class="rounded-2xl border border-red-200 bg-red-50 p-5">
            <h2 class="font-semibold text-red-900">
                Nonaktifkan Akun
            </h2>

            <p class="mt-1 text-sm text-red-700">
                Seluruh sesi pengguna akan dicabut.
            </p>

            <textarea
                wire:model="disableReason"
                rows="3"
                placeholder="Alasan penonaktifan..."
                class="mt-4 w-full rounded-xl border-red-200 bg-white text-sm"
            ></textarea>

            @error('disableReason')
                <p class="mt-2 text-xs text-red-700">{{ $message }}</p>
            @enderror

            <div class="mt-4 flex justify-end gap-2">
                <button
                    type="button"
                    wire:click="$set('disableUserId', null)"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600"
                >
                    Batal
                </button>

                <button
                    type="button"
                    wire:click="disable"
                    class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white"
                >
                    Nonaktifkan Akun
                </button>
            </div>
        </section>
    @endif
</div>
