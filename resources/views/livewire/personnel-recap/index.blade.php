<div @class(['recap-page space-y-6', 'has-detail' => $selectedPersonnel])>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div><h1 class="text-3xl font-bold tracking-tight text-slate-900">Rekap Personil</h1><p class="mt-1 text-sm text-slate-500">Ringkasan riwayat penugasan dan SPT terakhir setiap personil.</p></div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Total Personil',$totalPersonnel,'Seluruh personil'],['Personil Aktif',$activePersonnel,'Saat ini aktif'],['Total Penugasan',$totalAssignments,'Seluruh periode'],['SPT Bulan Ini',$monthAssignments,now()->translatedFormat('F Y')]] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center gap-4"><div class="stat-icon {{ $loop->index % 2 ? 'violet' : '' }}"><x-app.icon :name="['users', 'users', 'document', 'calendar'][$loop->index]" class="size-7" /></div><div><p class="text-sm text-slate-500">{{ $card[0] }}</p><p class="mt-1 text-3xl font-bold">{{ number_format($card[1]) }}</p><p class="text-xs text-slate-400">{{ $card[2] }}</p></div></div></div>
        @endforeach
    </div>

    <div class="personnel-overview">
        <section class="overview-card">
            <h2 class="font-bold">Jumlah Penugasan per Unit</h2>
            <div class="unit-bars">
                @forelse($unitStats as $unit)
                    <div><span>{{ $unit->name }}</span><div class="unit-bar-track"><div style="width: {{ ($unit->total / $maxUnit) * 100 }}%"></div></div><strong>{{ $unit->total }}</strong></div>
                @empty<p class="detail-empty">Belum ada data unit.</p>@endforelse
            </div>
        </section>
        <section class="overview-card">
            <h2 class="font-bold">Distribusi Status Personil</h2>
            <div class="personnel-distribution">
                <div class="personnel-donut" aria-hidden="true" style="background: {{ $totalPersonnel ? 'conic-gradient(#22c58b 0 '.($activePersonnel / $totalPersonnel * 100).'%, #f66b73 0 100%)' : '#e8eff7' }}"><div>{{ $totalPersonnel }}</div></div>
                <div class="distribution-legend">
                    <div><span class="bg-emerald-500"></span><p>Aktif</p><strong>{{ $activePersonnel }}</strong><small>{{ $totalPersonnel ? round($activePersonnel / $totalPersonnel * 100) : 0 }}%</small></div>
                    <div><span class="bg-red-400"></span><p>Tidak Aktif</p><strong>{{ $totalPersonnel - $activePersonnel }}</strong><small>{{ $totalPersonnel ? round(($totalPersonnel - $activePersonnel) / $totalPersonnel * 100) : 0 }}%</small></div>
                </div>
            </div>
        </section>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-3 text-sm text-blue-900">
            <span class="font-semibold">Mode rekap penugasan:</span>
            @if($recordType === 'normal')
                hanya SPT Normal. Koreksi Absensi tidak memengaruhi jumlah SPT, SPT terakhir, grafik unit, maupun statistik penugasan.
            @elseif($recordType === 'attendance_correction')
                hanya Koreksi Absensi.
            @else
                semua SPT, termasuk Koreksi Absensi.
            @endif
        </div>
        <h2 class="mb-4 font-semibold">Filter Data</h2><div class="recap-filters grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Tahun</span><select wire:model.live="year" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Tahun</option>@for($y=now()->year;$y>=2020;$y--)<option value="{{ $y }}">{{ $y }}</option>@endfor</select></label>
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Unit / Tim</span><select wire:model.live="unitId" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></label>
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Status</span><select wire:model.live="status" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Status</option><option value="active">Aktif</option><option value="inactive">Tidak Aktif</option></select></label>
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Jenis Kegiatan</span><select wire:model.live="activityTypeId" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Jenis Kegiatan</option>@foreach($activityTypes as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></label>
            <label class="grid gap-1.5 text-xs font-semibold text-slate-700"><span>Jenis Record</span><select wire:model.live="recordType" class="rounded-xl border-slate-200 text-sm"><option value="normal">SPT Normal (Default)</option><option value="attendance_correction">Koreksi Absensi</option><option value="all">Semua Jenis Record</option></select></label>
            <button wire:click="resetFilters" class="rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset Filter</button>
            <input wire:model.live.debounce.400ms="search" type="search" placeholder="Cari nama personil, NIP, atau unit..." class="rounded-xl border-slate-200 text-sm md:col-span-2 xl:col-span-3">
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4"><h2 class="text-lg font-bold">Daftar Rekap Personil</h2><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500">{{ $personnels->total() }} data</span></div><div class="overflow-x-auto"><table class="w-full min-w-[1100px] text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">No</th><th class="px-4 py-3">Nama Personil</th><th class="px-4 py-3">NIP</th><th class="px-4 py-3">Unit/Tim</th><th class="px-4 py-3">Jumlah SPT</th><th class="px-4 py-3">SPT Terakhir</th><th class="px-4 py-3">Tanggal Terakhir</th><th class="px-4 py-3">Kegiatan Terakhir</th><th class="px-4 py-3">Lokasi Terakhir</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($personnels as $person)<tr class="hover:bg-blue-50/40"><td class="px-4 py-3 text-slate-500">{{ $personnels->firstItem()+$loop->index }}</td><td class="px-4 py-3 font-semibold">{{ $person->name }}</td><td class="px-4 py-3 text-slate-600">{{ $person->nip ?: '-' }}</td><td class="px-4 py-3">{{ $person->unit?->name ?: '-' }}</td><td class="px-4 py-3 font-semibold text-blue-700">{{ $person->spt_count }}</td><td class="px-4 py-3 font-medium">{{ $person->latest_spt?->number ?: '-' }}</td><td class="px-4 py-3">{{ $person->latest_spt?->letter_date?->translatedFormat('d M Y') ?: '-' }}</td><td class="px-4 py-3">{{ $person->latest_spt?->activityType?->name ?: ($person->latest_spt?->subject ?: '-') }}</td><td class="px-4 py-3">{{ $person->latest_spt?->location ?: '-' }}</td><td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $person->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $person->is_active ? 'Aktif' : 'Tidak Aktif' }}</span></td><td class="px-4 py-3 text-right"><button wire:click="showDetail({{ $person->id }})" class="rounded-lg p-2 text-blue-600 hover:bg-blue-50">Detail</button></td></tr>@empty<tr><td colspan="11" class="px-6 py-12 text-center text-slate-500">Belum ada data personil.</td></tr>@endforelse</tbody></table></div><div class="border-t border-slate-100 px-5 py-4">{{ $personnels->links() }}</div></div>

    @if($selectedPersonnel)
        @include('livewire.personnel-recap.detail')
    @endif
</div>
