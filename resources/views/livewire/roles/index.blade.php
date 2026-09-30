<div class="portal-page">
    <x-app.page-heading title="Role &amp; Permission" description="Kelola hak akses pengguna untuk setiap peran dalam aplikasi." />

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach($roles as $role)
            <article class="portal-card">
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
