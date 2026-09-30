<div class="portal-page">
    <x-app.page-heading title="Kop & Administrasi Surat" description="Kelola identitas instansi, logo, dan penandatangan default.">
        <x-slot:actions>
            @can('settings.manage')
                <a
                    href="{{ route('administration-profiles.create') }}"
                    wire:navigate
                    class="spt-action spt-action-primary"
                >
                    + Profil Kop Baru
                </a>
            @endcan
        </x-slot:actions>
    </x-app.page-heading>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @error('profile')
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ $message }}
        </div>
    @enderror

    <div class="portal-card">
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Cari profil, instansi, atau penandatangan..."
            class="w-full rounded-xl border-slate-200 text-sm"
        >
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        @forelse($profiles as $profile)
            <article class="portal-card">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-slate-900">{{ $profile->name }}</h2>

                            @if($profile->is_default)
                                <span class="rounded-full bg-blue-50 px-2 py-1 text-[10px] font-semibold text-blue-700">
                                    DEFAULT
                                </span>
                            @endif
                        </div>

                        <p class="mt-1 text-sm text-slate-600">{{ $profile->organization_name }}</p>
                    </div>

                    <span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $profile->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ $profile->is_active ? 'AKTIF' : 'NONAKTIF' }}
                    </span>
                </div>

                <div class="mt-5 rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <x-letterhead-preview :profile="$profile" />
                </div>

                <dl class="mt-5 grid gap-3 text-xs sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Penandatangan</dt>
                        <dd class="mt-1 font-medium text-slate-800">{{ $profile->signatory_name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Jabatan</dt>
                        <dd class="mt-1 font-medium text-slate-800">{{ $profile->signatory_position ?: '-' }}</dd>
                    </div>
                </dl>

                @can('settings.manage')
                    <div class="mt-5 flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-4">
                        @if(!$profile->is_default)
                            <button
                                wire:click="setDefault({{ $profile->id }})"
                                type="button"
                                class="text-sm font-semibold text-blue-700"
                            >
                                Jadikan Default
                            </button>
                        @endif

                        <button
                            wire:click="toggle({{ $profile->id }})"
                            type="button"
                            class="text-sm text-slate-600"
                        >
                            {{ $profile->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>

                        <a
                            href="{{ route('administration-profiles.edit', $profile) }}"
                            wire:navigate
                            class="text-sm font-semibold text-blue-700"
                        >
                            Edit
                        </a>
                    </div>
                @endcan
            </article>
        @empty
            <div class="xl:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                Belum ada profil kop surat.
            </div>
        @endforelse
    </div>

    {{ $profiles->links() }}
</div>
