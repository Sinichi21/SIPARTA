<div class="dashboard-page portal-dashboard">
    <div class="dashboard-welcome"><div><h1 class="font-bold">Selamat Datang, {{ auth()->user()->name }}</h1><p class="mt-2 text-sm text-slate-500">Pantau surat tugas dan riwayat penugasan Anda dalam satu tempat.</p></div><div class="dashboard-date"><p>{{ now()->locale('id')->translatedFormat('l, d F Y') }} <x-app.icon name="sun" class="text-amber-500" /></p><span>Selamat menjalankan tugas hari ini.</span></div></div>
    <x-app.personnel-notice />
    <div class="dashboard-stats">
        <x-app.stat-card label="SPT Saya" :value="number_format($personalTotalSpt, 0, ',', '.')" description="Seluruh riwayat penugasan" icon="document" />
        <x-app.stat-card label="SPT Tahun Ini" :value="number_format($personalYearSpt, 0, ',', '.')" :description="'Periode '.now()->year" icon="calendar" tone="violet" />
        <x-app.stat-card label="Diterbitkan" :value="number_format($personalPublishedSpt, 0, ',', '.')" description="Surat tugas berstatus diterbitkan" icon="shield" tone="green" />
        <x-app.stat-card label="Mendatang" :value="number_format($personalUpcomingSpt, 0, ',', '.')" description="Penugasan setelah hari ini" icon="clock" tone="amber" />
    </div>

    <section class="portal-card">
        <header class="portal-card-heading">
            <h2><x-app.icon name="chart" /> Status Laporan SKP</h2>
            <span class="portal-caption">Satu laporan mewakili seluruh peserta SPT</span>
        </header>

        <div class="dashboard-stats">
            <x-app.stat-card label="Belum Dilaporkan" :value="number_format($personalSkpPending, 0, ',', '.')" description="SPT selesai tanpa laporan" icon="clock" tone="amber" />
            <x-app.stat-card label="Draft Laporan" :value="number_format($personalSkpDraft, 0, ',', '.')" description="Sedang disusun salah satu peserta" icon="document" tone="violet" />
            <x-app.stat-card label="Sudah Dilaporkan" :value="number_format($personalSkpSubmitted, 0, ',', '.')" description="Laporan berlaku untuk semua peserta" icon="shield" tone="green" />
        </div>

        <div class="mt-5 grid gap-3">
            @forelse($personalSkpQueue as $item)
                <a href="{{ route('my-spt.report', $item) }}" wire:navigate class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4 transition hover:bg-slate-50">
                    <div class="min-w-0">
                        <strong class="block truncate text-sm">{{ $item->number ?: 'SPT #'.$item->id }}</strong>
                        <span class="mt-1 block truncate text-xs text-slate-500">{{ $item->subject ?: $item->activityType?->name ?: '-' }}</span>
                        @if($item->sptReport)
                            <span class="mt-1 block text-xs text-slate-500">Draft oleh {{ $item->sptReport->creator?->name ?: '-' }}</span>
                        @endif
                    </div>
                    <span class="status-badge {{ $item->sptReport ? 'status-warning' : 'status-info' }}">
                        {{ $item->sptReport ? 'Draft' : 'Belum Dilaporkan' }}
                    </span>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                    Tidak ada laporan SKP yang memerlukan perhatian.
                </div>
            @endforelse
        </div>
    </section>
    <section class="portal-card">
        <header class="portal-card-heading"><h2><x-app.icon name="arrow" /> Akses Cepat</h2><span class="portal-caption">Administrasi Saya</span></header>
        <div class="portal-quick-links">
            @can('my-letters.view')<a href="{{ route('my-spt.index') }}" wire:navigate><span class="stat-icon"><x-app.icon name="document" /></span><span><strong>SPT Saya</strong><small>Telusuri surat tugas Anda</small></span><x-app.icon name="arrow" /></a>@endcan
            @can('my-reports.view')<a href="{{ route('my-recap.index') }}" wire:navigate><span class="stat-icon violet"><x-app.icon name="chart" /></span><span><strong>Rekap Saya</strong><small>Lihat ringkasan penugasan</small></span><x-app.icon name="arrow" /></a>@endcan
            <a href="{{ route('profile.edit') }}" wire:navigate><span class="stat-icon green"><x-app.icon name="users" /></span><span><strong>Profil Saya</strong><small>Kelola informasi akun</small></span><x-app.icon name="arrow" /></a>
        </div>
    </section>
    <div class="portal-dashboard-main">
        <section class="portal-card portal-table-card">
            <header class="portal-card-heading"><h2><x-app.icon name="document" /> SPT Saya Terbaru</h2>@can('my-letters.view')<a href="{{ route('my-spt.index') }}" wire:navigate class="portal-text-link">Lihat Semua <x-app.icon name="arrow" /></a>@endcan</header>
            <x-letters.personal-table :letters="$personalLatestSpt" />
            <footer class="portal-table-footer"><span class="portal-caption">Menampilkan hingga 5 surat tugas terbaru.</span></footer>
        </section>
        <aside class="portal-card portal-profile-card">
            <header class="portal-card-heading"><h2><x-app.icon name="users" /> Profil Personil</h2></header>
            @php($personnel = auth()->user()->personnel)
            <div class="portal-profile"><x-app.avatar class="sidebar-avatar" /><div><strong>{{ $personnel?->name ?: auth()->user()->name }}</strong><span>{{ $personnel?->position ?: 'Personil' }}</span></div></div>
            <dl class="spt-audit-fields mt-5"><div><dt>NIP</dt><dd>{{ $personnel?->nip ?: '-' }}</dd></div><div><dt>Unit / Tim</dt><dd>{{ $personnel?->unit?->name ?: '-' }}</dd></div><div><dt>Akun</dt><dd>{{ auth()->user()->email }}</dd></div></dl>
            <div class="portal-profile-footer"><span class="status-badge {{ $personnel ? 'status-success' : 'status-warning' }}">{{ $personnel ? 'Personil terhubung' : 'Belum terhubung' }}</span><a href="{{ route('profile.edit') }}" wire:navigate class="portal-text-link">Lihat Profil <x-app.icon name="arrow" /></a></div>
        </aside>
    </div>
</div>
