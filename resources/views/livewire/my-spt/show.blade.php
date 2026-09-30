<div class="spt-detail-page">
    <nav aria-label="Breadcrumb" class="spt-breadcrumb"><a href="{{ route('my-spt.index') }}" wire:navigate>SPT Saya</a><span aria-hidden="true">/</span><span aria-current="page">Detail SPT</span></nav>
    <x-app.page-heading title="Detail SPT Saya" description="Informasi surat dan pelaksanaan penugasan Anda."><x-slot:actions><a href="{{ route('my-spt.index') }}" wire:navigate class="spt-action spt-action-back"><x-app.icon name="arrow" class="rotate-180" /> Kembali ke SPT Saya</a></x-slot:actions></x-app.page-heading>
    <section class="spt-summary-card">
        <div class="spt-summary-title"><span class="spt-document-icon"><x-app.icon name="document" /></span><div class="min-w-0"><div class="mb-3 flex flex-wrap gap-2"><x-app.status-badge :status="$letter->status" /><span class="status-badge status-info">Surat Perintah Tugas</span></div><h2>{{ $letter->number ?: 'SPT #'.$letter->id }}</h2><p>{{ $letter->subject ?: $letter->activityType?->name ?: '-' }}</p></div></div>
        <div class="spt-summary-metrics">
            <div><x-app.icon name="calendar" /><span>Tanggal SPT<strong>{{ $letter->letter_date?->translatedFormat('d F Y') ?: '-' }}</strong></span></div>
            <div><x-app.icon name="clock" /><span>Periode Penugasan<strong>{{ $letter->start_date?->translatedFormat('d M Y') ?: '-' }}@if($letter->end_date && ! $letter->end_date->equalTo($letter->start_date)) &ndash; {{ $letter->end_date->translatedFormat('d M Y') }}@endif</strong></span></div>
            <div><x-app.icon name="pin" /><span>Lokasi<strong>{{ $letter->location ?: '-' }}</strong></span></div>
        </div>
    </section>
    <div class="spt-detail-grid">
        <section class="spt-section-card">
            <h2 class="spt-section-heading"><x-app.icon name="list" /> Informasi Penugasan</h2>
            <dl class="spt-information-grid"><div><dt>Jenis Kegiatan</dt><dd>{{ $letter->activityType?->name ?: 'Belum dikategorikan' }}</dd></div><div><dt>Lokasi</dt><dd>{{ $letter->location ?: '-' }}</dd></div></dl>
            <div class="spt-text-section"><h3>Dasar Penugasan</h3><p>{{ $letter->basis ?: 'Belum ada dasar penugasan yang dicatat.' }}</p></div>
            <div class="spt-text-section"><h3>Keterangan</h3><p>{{ $letter->description ?: 'Tidak ada keterangan tambahan.' }}</p></div>
        </section>
        <section class="spt-section-card">
            <h2 class="spt-section-heading"><x-app.icon name="users" /> Personil Anda</h2>
            @php($personnel = auth()->user()->personnel)
            <div class="portal-profile"><x-app.avatar class="sidebar-avatar" /><div><strong>{{ $personnel?->name ?: auth()->user()->name }}</strong><span>{{ $personnel?->position ?: 'Personil' }}</span></div></div>
            <dl class="spt-audit-fields mt-5"><div><dt>NIP</dt><dd>{{ $personnel?->nip ?: '-' }}</dd></div><div><dt>Unit / Tim</dt><dd>{{ $personnel?->unit?->name ?: '-' }}</dd></div></dl>
            @can('my-reports.view')<a href="{{ route('my-recap.index') }}" wire:navigate class="portal-text-link mt-5">Lihat Rekap Saya <x-app.icon name="arrow" /></a>@endcan
        </section>
    </div>
</div>
