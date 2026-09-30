<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Role & Permission</h1>
        <p class="mt-1 text-sm text-slate-500">
            Atur hak akses per role tanpa hardcode di halaman aplikasi.
        </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach($roles as $role)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-bold text-slate-900">{{ $role->name }}</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $role->permissions_count }} permission · {{ $role->users_count }} user
                        </p>
                    </div>

                    @if($role->name === 'super-admin')
                        <span class="rounded-full bg-purple-50 px-2 py-1 text-xs font-semibold text-purple-700">
                            Sistem
                        </span>
                    @else
                        @can('roles.manage')
                            <a
                                href="{{ route('roles.edit', $role) }}"
                                wire:navigate
                                class="text-sm font-semibold text-blue-700"
                            >
                                Atur
                            </a>
                        @endcan
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</div>
