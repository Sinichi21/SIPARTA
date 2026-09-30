<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Pengguna</h1>
            <p class="mt-1 text-sm text-slate-500">
                Kelola akun, role, status aktivasi, dan keterkaitan personil.
            </p>
        </div>

        @can('users.create')
            <a
                href="{{ route('users.create') }}"
                wire:navigate
                class="inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white"
            >
                + Tambah Pengguna
            </a>
        @endcan
    </div>

    @foreach(['success' => 'emerald', 'warning' => 'amber'] as $key => $tone)
        @if(session($key))
            <div class="rounded-xl border p-4 text-sm {{ $tone === 'emerald' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                {{ session($key) }}
            </div>
        @endif
    @endforeach

    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_200px]">
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Cari nama atau email..."
            class="rounded-xl border-slate-200 text-sm"
        >

        <select wire:model.live="status" class="rounded-xl border-slate-200 text-sm">
            <option value="all">Semua status</option>
            <option value="active">Aktif</option>
            <option value="pending">Menunggu aktivasi</option>
            <option value="disabled">Nonaktif</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Pengguna</th>
                        <th class="px-5 py-3">Personil</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Keamanan</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @foreach($users as $user)
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="block text-slate-900">{{ $user->name }}</strong>
                                <span class="text-xs text-slate-500">{{ $user->email }}</span>
                            </td>

                            <td class="px-5 py-4 text-xs">
                                {{ $user->personnel?->name ?: '-' }}
                            </td>

                            <td class="px-5 py-4">
                                <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">
                                    {{ $user->getRoleNames()->first() ?: 'Tanpa role' }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @if($user->must_set_password)
                                        <span class="rounded-full bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700">
                                            Menunggu Aktivasi
                                        </span>
                                    @elseif($user->account_disabled_at)
                                        <span class="rounded-full bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-700">
                                            Nonaktif
                                        </span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700">
                                            Aktif
                                        </span>
                                    @endif

                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">
                                        2FA {{ $user->two_factor_confirmed_at ? 'Aktif' : 'Belum' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-5 py-4 text-right">
                                @can('users.update')
                                    <a
                                        href="{{ route('users.edit', $user) }}"
                                        wire:navigate
                                        class="text-sm font-semibold text-blue-700"
                                    >
                                        Edit
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{ $users->links() }}
</div>
