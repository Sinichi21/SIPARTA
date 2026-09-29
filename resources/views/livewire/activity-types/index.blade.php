<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">
                Jenis Kegiatan
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Master kegiatan untuk SPT.
            </p>
        </div>

        @can('activity-types.manage')
            <a
                href="{{ route('activity-types.create') }}"
                wire:navigate
                class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white"
            >
                + Tambah Jenis Kegiatan
            </a>
        @endcan
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-[1fr_200px]">
            <input
                wire:model.live.debounce.300ms="search"
                placeholder="Cari..."
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
                            Nama
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
                    @foreach ($activityTypes as $activityType)
                        <tr>
                            <td class="px-4 py-3 text-sm">
                                {{ $activityType->code ?: '-' }}
                            </td>

                            <td class="px-4 py-3">
                                <p class="text-sm font-medium">
                                    {{ $activityType->name }}
                                </p>

                                @if ($activityType->description)
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $activityType->description }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $activityType->is_active ? 'Aktif' : 'Nonaktif' }}
                            </td>

                            <td class="px-4 py-3 text-right text-sm">
                                @can('activity-types.manage')
                                    <a
                                        href="{{ route('activity-types.edit', $activityType) }}"
                                        wire:navigate
                                        class="font-medium text-blue-700"
                                    >
                                        Edit
                                    </a>

                                    @if ($activityType->is_active)
                                        <button
                                            wire:click="deactivate({{ $activityType->id }})"
                                            wire:confirm="Nonaktifkan jenis kegiatan?"
                                            class="ml-3 text-red-600"
                                        >
                                            Nonaktifkan
                                        </button>
                                    @else
                                        <button
                                            wire:click="activate({{ $activityType->id }})"
                                            class="ml-3 text-green-700"
                                        >
                                            Aktifkan
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-4">
            {{ $activityTypes->links() }}
        </div>
    </div>
</div>