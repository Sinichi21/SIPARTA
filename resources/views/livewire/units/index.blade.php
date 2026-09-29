<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">
                Unit / Tim
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Kelola unit organisasi tanpa hardcode di aplikasi.
            </p>
        </div>

        @can('units.manage')
            <a
                href="{{ route('units.create') }}"
                wire:navigate
                class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white"
            >
                + Tambah Unit
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-[1fr_200px]">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari unit atau kode..."
                class="rounded-lg border border-slate-300 px-3 py-2"
            >

            <select
                wire:model.live="status"
                class="rounded-lg border border-slate-300 px-3 py-2"
            >
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
                <option value="all">Semua</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Kode
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Unit
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Personil
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Status
                        </th>

                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-blue-900">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($units as $unit)
                        <tr>
                            <td class="px-4 py-3 text-sm">
                                {{ $unit->code ?: '-' }}
                            </td>

                            <td class="px-4 py-3">
                                <p class="text-sm font-medium">
                                    {{ $unit->name }}
                                </p>

                                @if ($unit->description)
                                    <p class="mt-1 max-w-xl text-xs text-slate-500">
                                        {{ $unit->description }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $unit->personnels_count }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $unit->is_active ? 'Aktif' : 'Nonaktif' }}
                            </td>

                            <td class="px-4 py-3 text-right text-sm">
                                @can('units.manage')
                                    <a
                                        href="{{ route('units.edit', $unit) }}"
                                        wire:navigate
                                        class="font-medium text-blue-700"
                                    >
                                        Edit
                                    </a>

                                    @if ($unit->is_active)
                                        <button
                                            type="button"
                                            wire:click="deactivate({{ $unit->id }})"
                                            wire:confirm="Nonaktifkan unit ini?"
                                            class="ml-3 font-medium text-red-600"
                                        >
                                            Nonaktifkan
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="activate({{ $unit->id }})"
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
                                colspan="5"
                                class="px-4 py-10 text-center text-sm text-slate-500"
                            >
                                Belum ada unit.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-4">
            {{ $units->links() }}
        </div>
    </div>
</div>