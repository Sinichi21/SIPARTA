<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div><h1 class="text-3xl font-bold tracking-tight text-slate-900">Rekap Personil</h1><p class="mt-1 text-sm text-slate-500">Ringkasan riwayat penugasan dan SPT terakhir setiap personil.</p></div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Total Personil',$totalPersonnel,'Seluruh personil'],['Personil Aktif',$activePersonnel,'Saat ini aktif'],['Total Penugasan',$totalAssignments,'Seluruh periode'],['SPT Bulan Ini',$monthAssignments,now()->translatedFormat('F Y')]] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center gap-4"><div class="flex size-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg></div><div><p class="text-sm text-slate-500">{{ $card[0] }}</p><p class="mt-1 text-3xl font-bold">{{ number_format($card[1]) }}</p><p class="text-xs text-slate-400">{{ $card[2] }}</p></div></div></div>
        @endforeach
    </div>

    @if(($allPersonnelSpt ?? 0) > 0)
        <div class="rounded-2xl border border-blue-100 bg-blue-50/70 px-4 py-3 text-sm text-blue-900">
            <span class="font-semibold">
                {{ number_format($allPersonnelSpt) }} SPT Seluruh Pegawai
            </span>
            <span class="text-blue-700">
                pada filter aktif. SPT ini tetap tercatat dalam rekap, tetapi tidak ditambahkan ke jumlah SPT personil individual.
            </span>
        </div>
    @endif

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2"><h2 class="font-bold">Jumlah Penugasan per Unit</h2><div class="mt-5 space-y-4">@forelse($unitStats as $unit)<div><div class="mb-1 flex justify-between text-sm"><span class="text-slate-600">{{ $unit->name }}</span><span class="font-semibold">{{ $unit->total }}</span></div><div class="h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-500" style="width: {{ ($unit->total / $maxUnit) * 100 }}%"></div></div></div>@empty<div class="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">Belum ada data unit.</div>@endforelse</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Distribusi Status Personil</h2><div class="mt-6 grid place-items-center"><div class="grid size-36 place-items-center rounded-full" style="background: conic-gradient(#22c55e 0 {{ $totalPersonnel ? ($activePersonnel/$totalPersonnel)*100 : 0 }}%, #ef4444 0 100%);"><div class="grid size-24 place-items-center rounded-full bg-white"><span class="text-2xl font-bold">{{ $totalPersonnel }}</span></div></div></div><div class="mt-6 space-y-2 text-sm"><div class="flex justify-between"><span class="text-slate-500">Aktif</span><span class="font-semibold">{{ $activePersonnel }}</span></div><div class="flex justify-between"><span class="text-slate-500">Tidak Aktif</span><span class="font-semibold">{{ $totalPersonnel-$activePersonnel }}</span></div></div></div>
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
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <select wire:model.live="year" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Tahun</option>@for($y=now()->year;$y>=2020;$y--)<option value="{{ $y }}">{{ $y }}</option>@endfor</select>
            <select wire:model.live="unitId" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select>
            <select wire:model.live="status" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Status</option><option value="active">Aktif</option><option value="inactive">Tidak Aktif</option></select>
            <select wire:model.live="activityTypeId" class="rounded-xl border-slate-200 text-sm"><option value="">Semua Jenis Kegiatan</option>@foreach($activityTypes as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
            <select wire:model.live="recordType" class="rounded-xl border-slate-200 text-sm"><option value="normal">SPT Normal (Default)</option><option value="attendance_correction">Koreksi Absensi</option><option value="all">Semua Jenis Record</option></select>
            <button wire:click="resetFilters" class="rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset Filter</button>
            <input wire:model.live.debounce.400ms="search" type="search" placeholder="Cari nama personil, NIP, atau unit..." class="rounded-xl border-slate-200 text-sm md:col-span-2 xl:col-span-6">
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4"><h2 class="text-lg font-bold">Daftar Rekap Personil</h2><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500">{{ $personnels->total() }} data</span></div><div class="overflow-x-auto"><table class="w-full min-w-[1100px] text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">No</th><th class="px-4 py-3">Nama Personil</th><th class="px-4 py-3">NIP</th><th class="px-4 py-3">Unit/Tim</th><th class="px-4 py-3">Jumlah SPT</th><th class="px-4 py-3">SPT Terakhir</th><th class="px-4 py-3">Tanggal Terakhir</th><th class="px-4 py-3">Kegiatan Terakhir</th><th class="px-4 py-3">Lokasi Terakhir</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($personnels as $person)<tr class="hover:bg-blue-50/40"><td class="px-4 py-3 text-slate-500">{{ $personnels->firstItem()+$loop->index }}</td><td class="px-4 py-3 font-semibold">{{ $person->name }}</td><td class="px-4 py-3 text-slate-600">{{ $person->nip ?: '-' }}</td><td class="px-4 py-3">{{ $person->unit?->name ?: '-' }}</td><td class="px-4 py-3 font-semibold text-blue-700">{{ $person->spt_count }}</td><td class="px-4 py-3 font-medium">{{ $person->latest_spt?->number ?: '-' }}</td><td class="px-4 py-3">{{ $person->latest_spt?->letter_date?->translatedFormat('d M Y') ?: '-' }}</td><td class="px-4 py-3">{{ $person->latest_spt?->activityType?->name ?: ($person->latest_spt?->subject ?: '-') }}</td><td class="px-4 py-3">{{ $person->latest_spt?->location ?: '-' }}</td><td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $person->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $person->is_active ? 'Aktif' : 'Tidak Aktif' }}</span></td><td class="px-4 py-3 text-right"><button wire:click="showDetail({{ $person->id }})" class="rounded-lg p-2 text-blue-600 hover:bg-blue-50">Detail</button></td></tr>@empty<tr><td colspan="11" class="px-6 py-12 text-center text-slate-500">Belum ada data personil.</td></tr>@endforelse</tbody></table></div><div class="border-t border-slate-100 px-5 py-4">{{ $personnels->links() }}</div></div>

    @if($selectedPersonnel)
        @include('livewire.personnel-recap.detail')
    @endif

</div>
