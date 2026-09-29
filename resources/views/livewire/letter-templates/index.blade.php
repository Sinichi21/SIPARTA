<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Template Surat</h1>
            <p class="mt-1 text-sm text-slate-500">
                Kelola isi dokumen, placeholder, versi, dan template default.
            </p>
        </div>

        @can('settings.manage')
            <a
                href="{{ route('letter-templates.create') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white"
            >
                + Template Baru
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Cari nama atau kode template..."
            class="w-full rounded-xl border-slate-200 text-sm"
        >
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Jenis Surat</th>
                        <th class="px-4 py-3">Versi</th>
                        <th class="px-4 py-3">Default</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($templates as $template)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $template->name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $template->code }}</td>
                            <td class="px-4 py-3">{{ $template->letterType?->name ?: '-' }}</td>
                            <td class="px-4 py-3">v{{ $template->version }}</td>
                            <td class="px-4 py-3">{{ $template->is_default ? 'Ya' : '-' }}</td>
                            <td class="px-4 py-3">{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('settings.manage')
                                    <a
                                        href="{{ route('letter-templates.edit', $template) }}"
                                        wire:navigate
                                        class="text-sm font-semibold text-blue-700"
                                    >
                                        Edit / Preview
                                    </a>

                                    <button
                                        type="button"
                                        wire:click="toggle({{ $template->id }})"
                                        class="ml-3 text-sm text-slate-600"
                                    >
                                        {{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                Belum ada template surat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 p-4">
            {{ $templates->links() }}
        </div>
    </div>
</div>
