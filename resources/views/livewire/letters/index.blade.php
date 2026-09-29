<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Data SPT</h1>
            <p class="mt-1 text-sm text-slate-500">
                Data Surat Perintah Tugas dan personil yang ditugaskan.
            </p>
        </div>

        @can('letters.create')
            <a
                href="{{ route('letters.create') }}"
                wire:navigate
                class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800"
            >
                + Tambah SPT
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-6">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari SPT..."
                class="rounded-lg border border-slate-300 px-3 py-2 lg:col-span-2"
            >

            <select wire:model.live="status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Semua status</option>
                <option value="draft">Draft</option>
                <option value="published">Diterbitkan</option>
                <option value="cancelled">Dibatalkan</option>
            </select>

            <select wire:model.live="activityType" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Semua kegiatan</option>
                @foreach ($activityTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>

            <input
                type="number"
                wire:model.live="year"
                placeholder="Tahun"
                min="2000"
                max="2100"
                class="rounded-lg border border-slate-300 px-3 py-2"
            >

            <select wire:model.live="month" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Semua bulan</option>
                @foreach (range(1, 12) as $number)
                    <option value="{{ $number }}">
                        {{ \Carbon\Carbon::create()->month($number)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Nomor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Kegiatan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Lokasi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Personil</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Jenis Record</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-blue-900">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($letters as $letter)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium">
                                {{ $letter->number ?: 'Belum bernomor' }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $letter->letter_date?->format('d/m/Y') ?: '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $letter->activityType?->name ?: '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $letter->location ?: '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $letter->personnels->count() }} personil
                            </td>

                            <td class="px-4 py-3 text-sm">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $letter->record_type?->value === 'attendance_correction' ? 'bg-violet-50 text-violet-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $letter->record_type?->label() ?? 'SPT Normal' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $letter->status->label() }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="spt-row-actions">
                                    <a href="{{ route('letters.show', $letter) }}" wire:navigate class="spt-action spt-action-view"><x-app.icon name="document" /> Detail</a>
                                    <x-letters.edit-action :letter="$letter" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">
                                Belum ada data SPT.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-4">
            {{ $letters->links() }}
        </div>
    </div>
</div>