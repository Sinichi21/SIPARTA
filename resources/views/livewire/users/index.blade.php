<div class="portal-page">
    <x-app.page-heading title="Pengguna" description="Kelola akun, role, status aktivasi, dan keterkaitan personil.">
        <x-slot:actions>
            @can('users.create')
                <a
                    href="{{ route('users.create') }}"
                    wire:navigate
                    class="spt-action spt-action-primary"
                >
                    + Tambah Pengguna
                </a>
            @endcan
        </x-slot:actions>
    </x-app.page-heading>

    @foreach(['success' => 'emerald', 'warning' => 'amber'] as $key => $tone)
        @if(session($key))
            <div class="rounded-xl border p-4 text-sm {{ $tone === 'emerald' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                {{ session($key) }}
            </div>
        @endif
    @endforeach

    <div class="grid gap-3 portal-card md:grid-cols-[1fr_200px]">
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

    <div class="portal-card portal-table-card">
        <div class="overflow-x-auto">
            <table class="portal-table">
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
                    @forelse($users as $user)
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
                    @empty
                        <tr><td colspan="5"><x-app.empty-state title="Tidak ada pengguna" description="Coba ubah pencarian atau filter status pengguna." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $users->links() }}
</div>
