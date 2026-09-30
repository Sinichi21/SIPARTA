<div class="portal-page">
    <x-app.page-heading title="SPT Saya" description="Temukan surat tugas dan informasi penugasan Anda.">
        <x-slot:actions>@can('my-reports.view')<a href="{{ route('my-recap.index') }}" wire:navigate class="spt-action spt-action-edit"><x-app.icon name="chart" /> Rekap Saya</a>@endcan</x-slot:actions>
    </x-app.page-heading>
    <x-app.personnel-notice />
    <section class="portal-card">
        <header class="portal-card-heading"><h2><x-app.icon name="list" /> Filter Surat Tugas</h2><span class="portal-caption">Sesuaikan pencarian Anda</span></header>
        <div class="portal-filters">
            <label class="portal-search"><span>Cari SPT</span><input wire:model.live.debounce.300ms="search" type="search" placeholder="Nomor SPT, kegiatan, atau lokasi..."></label>
            <label><span>Tahun</span><select wire:model.live="year"><option value="">Semua tahun</option>@foreach(collect($years)->push(now()->year)->unique()->sortDesc() as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach</select></label>
            <button type="button" wire:click="resetFilters" class="spt-action spt-action-back">Reset Filter</button>
        </div>
    </section>
    <section class="portal-card portal-table-card">
        <header class="portal-card-heading"><h2><x-app.icon name="document" /> Daftar SPT Saya <span class="count-badge">{{ $letters->total() }} surat</span></h2><span class="portal-caption">{{ $year ?: 'Semua tahun' }}</span></header>
        <x-letters.personal-table :letters="$letters" :offset="($letters->firstItem() ?? 1) - 1" :filtered="filled($search) || filled($year)" />
        <footer class="portal-table-footer">{{ $letters->links() }}</footer>
    </section>
</div>
