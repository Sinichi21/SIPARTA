<div class="portal-page">
    <x-app.page-heading title="Rekap Saya" description="Ringkasan surat tugas dan riwayat penugasan Anda.">
        <x-slot:actions>@can('my-letters.view')<a href="{{ route('my-spt.index') }}" wire:navigate class="spt-action spt-action-edit"><x-app.icon name="document" /> SPT Saya</a>@endcan</x-slot:actions>
    </x-app.page-heading>
    <x-app.personnel-notice />
    <section class="portal-card portal-period-filter">
        <div><h2 class="font-semibold text-sm">Periode Rekap</h2><p class="portal-caption mt-1">Ringkasan berdasarkan tahun pada tanggal SPT.</p></div>
        <label><span>Tahun</span><select wire:model.live="year"><option value="">Semua tahun</option>@foreach(collect($years)->push(now()->year)->unique()->sortDesc() as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach</select></label>
    </section>
    <div class="portal-stats-three">
        <x-app.stat-card label="Total SPT" :value="number_format($total, 0, ',', '.')" :description="$year ? 'Tahun '.$year : 'Seluruh periode'" icon="document" />
        <x-app.stat-card label="Diterbitkan" :value="number_format($published, 0, ',', '.')" description="Surat tugas berstatus diterbitkan" icon="shield" tone="green" />
        <x-app.stat-card label="SPT Terakhir" :value="$latest?->letter_date?->translatedFormat('d M Y') ?: '-'" description="Tanggal surat terbaru pada periode ini" icon="calendar" tone="violet" />
    </div>
    <div class="portal-report-overview">
        <section class="portal-card">
            <header class="portal-card-heading"><h2><x-app.icon name="chart" /> Ringkasan Status</h2></header>
            <div class="portal-status-chart">
                <div class="personnel-donut" aria-hidden="true" style="background: {{ $total ? 'conic-gradient(#22c58b 0 '.($published / $total * 100).'%, #87b7f5 0 100%)' : '#e8eff7' }}"><div>{{ $total }}</div></div>
                <dl class="portal-chart-legend"><div><dt><i class="bg-emerald-500"></i>Diterbitkan</dt><dd>{{ $published }} <span>{{ $total ? round($published / $total * 100) : 0 }}%</span></dd></div><div><dt><i class="bg-blue-300"></i>Status lainnya</dt><dd>{{ $total - $published }} <span>{{ $total ? round(($total - $published) / $total * 100) : 0 }}%</span></dd></div></dl>
            </div>
        </section>
        <section class="portal-card">
            <header class="portal-card-heading"><h2><x-app.icon name="clock" /> Penugasan Terbaru</h2>@if($latest)<x-app.status-badge :status="$latest->status" />@endif</header>
            @if($latest)
                <h3 class="portal-latest-title">{{ $latest->subject ?: $latest->activityType?->name ?: '-' }}</h3><p class="portal-caption mt-1 break-words">{{ $latest->number ?: 'SPT #'.$latest->id }}</p>
                <div class="portal-latest-meta"><span><x-app.icon name="calendar" /> {{ $latest->letter_date?->translatedFormat('d M Y') ?: '-' }}</span><span><x-app.icon name="pin" /> {{ $latest->location ?: '-' }}</span></div>
                @can('my-letters.view')<a href="{{ route('my-spt.show', $latest) }}" wire:navigate class="portal-text-link">Lihat Detail SPT <x-app.icon name="arrow" /></a>@endcan
            @else<x-app.empty-state title="Belum ada penugasan" description="Pilih tahun lain untuk melihat riwayat Anda." icon="calendar" />@endif
        </section>
    </div>
    <section class="portal-card portal-table-card">
        <header class="portal-card-heading"><h2><x-app.icon name="document" /> Riwayat Penugasan <span class="count-badge">{{ $total }} surat</span></h2><span class="portal-caption">{{ $year ?: 'Semua tahun' }}</span></header>
        <x-letters.personal-table :letters="$history" :filtered="filled($year)" />
        <footer class="portal-table-footer portal-history-footer"><span>Menampilkan {{ $history->count() }} surat terbaru dari {{ $total }} surat.</span>@can('my-letters.view')<a href="{{ route('my-spt.index', ['year' => $year]) }}" wire:navigate class="portal-text-link">Lihat Semua <x-app.icon name="arrow" /></a>@endcan</footer>
    </section>
</div>
