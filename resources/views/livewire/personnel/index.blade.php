<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                Data Personil
            </h1>

            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Kelola personil yang dapat ditugaskan pada SPT.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('personnels.merge')
                <a
                    href="{{ route('personnel-duplicates.index') }}"
                    wire:navigate
                    class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-100"
                >
                    Deteksi Duplikat
                </a>
            @endcan

            @can('personnels.create')
                <a
                    href="{{ route('personnels.create') }}"
                    wire:navigate
                    class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800"
                >
                    + Tambah Personil
                </a>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-[1fr_200px] dark:border-slate-700">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama, NIP, atau jabatan..."
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800"
            >

            <select
                wire:model.live="status"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
                <option value="all">Semua</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-blue-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Nama
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            NIP
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Unit
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Jabatan
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Status
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-blue-900">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($personnels as $personnel)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium">
                                {{ $personnel->name }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $personnel->nip ?: '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $personnel->unit?->name ?: '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $personnel->position ?: '-' }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($personnel->is_active)
                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right text-sm">
                                @can('personnels.update')
                                    <a
                                        href="{{ route('personnels.edit', $personnel) }}"
                                        wire:navigate
                                        class="font-medium text-blue-700 hover:text-blue-900"
                                    >
                                        Edit
                                    </a>

                                    @if ($personnel->is_active)
                                        <button
                                            type="button"
                                            wire:click="deactivate({{ $personnel->id }})"
                                            wire:confirm="Nonaktifkan personil ini?"
                                            class="ml-3 font-medium text-red-600"
                                        >
                                            Nonaktifkan
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="activate({{ $personnel->id }})"
                                            class="ml-3 font-medium text-green-700"
                                        >
                                            Aktifkan
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-sm text-slate-500"
                            >
                                Belum ada data personil.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-4 dark:border-slate-700">
            {{ $personnels->links() }}
        </div>
    </div>
</div>