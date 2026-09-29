<div @class(['recap-page space-y-6', 'has-detail' => $selectedLetter])>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Rekap SPT</h1>
            <p class="mt-1 text-sm text-slate-500">Ringkasan seluruh Surat Perintah Tugas dan personil yang terlibat.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('reports.view')
                <button
                    type="button"
                    wire:click="exportCsv"
                    wire:loading.attr="disabled"
                    wire:target="exportCsv"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
                    title="Export seluruh Rekap SPT sesuai filter aktif"
                >
                    <x-app.icon name="download" class="size-4" />
                    <span wire:loading.remove wire:target="exportCsv">
                        Export CSV
                    </span>
                    <span wire:loading wire:target="exportCsv">
                        Menyiapkan...
                    </span>
                </button>
            @endcan

            @can('letters.import')
                <a href="{{ route('spt-import.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Import SPT Lama</a>
            @endcan
            @can('letters.create')
                <a href="{{ route('letters.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">+ SPT Baru</a>
            @endcan
        </div>
    </div>

    <div class="rounded-2xl border border-blue-100 bg-blue-50/70 px-4 py-3 text-sm text-blue-900">
        <span class="font-semibold">Mode rekap:</span>
        @if($recordType === 'normal')
            hanya SPT Normal. Koreksi Absensi tidak dihitung.
        @elseif($recordType === 'attendance_correction')
            hanya Koreksi Absensi.
        @else
            semua SPT, termasuk Koreksi Absensi.
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total SPT', 'value' => number_format($totalSpt), 'sub' => 'Sesuai filter aktif', 'tone' => 'blue'],
            ['label' => 'SPT Bulan Ini', 'value' => number_format($monthSpt), 'sub' => now()->translatedFormat('F Y'), 'tone' => 'violet'],
            ['label' => 'Personil Terlibat', 'value' => number_format($personnelCount), 'sub' => 'Personil unik', 'tone' => 'sky'],
            ['label' => 'Jenis Kegiatan', 'value' => number_format($activityCount), 'sub' => 'Jenis kegiatan terpakai', 'tone' => 'indigo'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="stat-icon {{ $loop->index % 2 ? 'violet' : '' }}">
                        <x-app.icon :name="['document', 'calendar', 'users', 'list'][$loop->index]" class="size-7" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-1 text-3xl font-bold text-slate-900">{{ $card['value'] }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $card['sub'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Jumlah SPT per Bulan ({{ $year ?: now()->year }})</h2>
                <span class="text-xs text-slate-400">12 bulan</span>
            </div>
            <div class="flex h-56 items-end gap-3 border-b border-slate-100 px-2 pb-1">
                @foreach ($monthly as $item)
                    <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                        <div class="group relative flex w-full items-end justify-center" style="height: 180px">
                            <div class="w-full max-w-10 rounded-t bg-gradient-to-t from-blue-400 to-blue-300 transition hover:from-blue-600 hover:to-blue-500" style="height: {{ ($item['total'] / $maxMonthly) * 100 }}%"></div>
                            <div class="pointer-events-none absolute -top-8 hidden rounded bg-slate-900 px-2 py-1 text-xs text-white group-hover:block">{{ $item['total'] }} SPT</div>
                        </div>
                        <span class="text-[11px] text-slate-500">{{ $item['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold text-slate-900">Jenis Kegiatan ({{ $year ?: now()->year }})</h2>
            @php
                $chartColors = ['#3086fa', '#20bfb8', '#32c875', '#ffb33b', '#a16afa'];
                $chartTotal = $activities->sum('total');
                $chartOffset = 0;
                $chartStops = [];
                foreach ($activities as $index => $activity) {
                    $nextOffset = $chartOffset + ($chartTotal ? $activity->total / $chartTotal * 100 : 0);
                    $chartStops[] = $chartColors[$index % 5].' '.$chartOffset.'% '.$nextOffset.'%';
                    $chartOffset = $nextOffset;
                }
            @endphp
            <div class="mt-5 flex flex-wrap items-center justify-center gap-6">
                <div aria-hidden="true" class="grid size-36 shrink-0 place-items-center rounded-full" style="background: {{ $chartTotal ? 'conic-gradient('.implode(', ', $chartStops).')' : '#e8eff7' }}">
                    <div class="grid size-24 place-items-center rounded-full bg-white"><span class="text-xl font-bold">{{ $chartTotal }}</span></div>
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    @forelse ($activities as $activity)
                        <div class="flex items-center gap-2 text-xs">
                            <span class="size-2.5 shrink-0 rounded-full" style="background: {{ $chartColors[$loop->index % 5] }}"></span>
                            <span class="flex-1 text-slate-500">{{ $activity->name }}</span>
                            <span class="font-semibold">{{ $chartTotal ? round($activity->total / $chartTotal * 100) : 0 }}%</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada data kegiatan.</p>
                    @endforelse
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-400">Proporsi hingga 5 jenis kegiatan terbanyak.</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 font-semibold">Filter Data</h2><div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Tahun</span><select wire:model.live="year" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Tahun</option>@foreach($years as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach</select></label>
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Personil</span><select wire:model.live="personnelId" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Personil</option>@foreach($personnels as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></label>
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Unit / Tim</span><select wire:model.live="unitId" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Unit</option>@foreach($units as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></label>
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Status</span><select wire:model.live="status" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Status</option><option value="draft">Draft</option><option value="published">Diterbitkan</option><option value="cancelled">Dibatalkan</option><option value="archived">Diarsipkan</option></select></label>
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Jenis Record</span><select wire:model.live="recordType" class="rounded-xl border-slate-200 text-sm"><option value="normal">SPT Normal (Default)</option><option value="attendance_correction">Koreksi Absensi</option><option value="all">Semua Jenis Record</option></select></label>
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Jenis Kegiatan</span><select wire:model.live="activityTypeId" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Jenis Kegiatan</option>@foreach($activityTypes as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></label>
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Lokasi</span><input wire:model.live.debounce.400ms="location" type="text" placeholder="Semua lokasi" class="rounded-xl border-slate-200 text-sm"></label>
            <div class="md:col-span-2 flex gap-2">
                <input wire:model.live.debounce.400ms="search" type="search" placeholder="Cari nomor SPT, personil, kegiatan..." class="min-w-0 flex-1 rounded-xl border-slate-200 text-sm">
                <button wire:click="resetFilters" class="rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</button>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4"><h2 class="text-lg font-bold text-slate-900">Daftar SPT</h2><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">{{ $letters->total() }} data</span></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1050px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">No</th><th class="px-4 py-3">Nomor SPT</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Periode</th><th class="px-4 py-3">Kegiatan</th><th class="px-4 py-3">Lokasi</th><th class="px-4 py-3">Personil</th><th class="px-4 py-3">Jenis Record</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Sumber</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($letters as $letter)
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-4 py-3 text-slate-500">{{ $letters->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $letter->number ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $letter->letter_date?->translatedFormat('d M Y') ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $letter->start_date?->format('d M') ?: '-' }}{{ $letter->end_date && !$letter->end_date->equalTo($letter->start_date) ? ' – '.$letter->end_date->translatedFormat('d M Y') : '' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $letter->activityType?->name ?: $letter->subject }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $letter->location ?: '-' }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ $letter->assignsAllPersonnel() ? 'Seluruh Pegawai' : $letter->personnels_count.' orang' }}</span></td><td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $letter->record_type?->value === 'attendance_correction' ? 'bg-violet-50 text-violet-700' : 'bg-slate-100 text-slate-600' }}">{{ $letter->record_type?->label() ?? 'SPT Normal' }}</span></td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $letter->status->value === 'published' ? 'bg-emerald-50 text-emerald-700' : ($letter->status->value === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">{{ $letter->status->label() }}</span></td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $letter->source === 'import' ? 'bg-orange-50 text-orange-700' : 'bg-blue-50 text-blue-700' }}">{{ $letter->source === 'import' ? 'Import Arsip' : 'Dibuat dari Sistem' }}</span></td>
                            <td class="px-4 py-3 text-right"><button wire:click="showDetail({{ $letter->id }})" class="rounded-lg p-2 text-blue-600 hover:bg-blue-50">Lihat</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-6 py-12 text-center text-slate-500">Belum ada data SPT.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">{{ $letters->links() }}</div>
    </div>

    @if($selectedLetter)
        @include('livewire.spt-recap.detail')
    @endif
</div>
