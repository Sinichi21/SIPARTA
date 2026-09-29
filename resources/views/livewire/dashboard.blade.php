<div class="dashboard-page">
    <div class="dashboard-welcome">
        <div><h1 class="font-bold">Selamat Datang, {{ auth()->user()->name }}</h1><p class="mt-2 text-sm text-slate-500">Kelola surat dan perjalanan dinas (SPT) dengan lebih efektif dan terintegrasi.</p></div>
        <div class="dashboard-date"><p>{{ now()->locale('id')->translatedFormat('l, d F Y') }} <x-app.icon name="sun" class="text-amber-500" /></p><span>Tetap semangat dalam memberikan pelayanan terbaik.</span></div>
    </div>
    <div class="dashboard-stats">
        @foreach ([
            ['Total SPT', $totalSpt, 'Seluruh surat perintah tugas', 'document', ''],
            ['Diterbitkan', $publishedSpt, 'SPT yang telah diterbitkan', 'document', 'green'],
            ['SPT Bulan Ini', $monthlySpt, now()->locale('id')->translatedFormat('F Y'), 'calendar', 'violet'],
            ['Arsip SPT', $archivedSpt, 'Surat yang telah diarsipkan', 'archive', 'amber'],
        ] as [$label, $value, $description, $icon, $tone])
            <section class="dashboard-stat"><div class="stat-icon {{ $tone }}"><x-app.icon :name="$icon" class="size-8" /></div><div class="min-w-0"><p class="text-sm font-semibold text-slate-500">{{ $label }}</p><strong>{{ number_format($value, 0, ',', '.') }}</strong><p class="text-xs text-slate-500">{{ $description }}</p></div></section>
        @endforeach
    </div>
    <div class="dashboard-main">
        <section class="dashboard-card dashboard-letters">
            <header class="dashboard-card-heading"><h2><x-app.icon class="text-blue-600" /> SPT Terbaru</h2>@can('letters.view')<a href="{{ route('letters.index') }}" wire:navigate>Lihat Semua <x-app.icon name="arrow" /></a>@endcan</header>
            <div class="dashboard-table-scroll">
                <table class="dashboard-table"><thead><tr><th>No</th><th>No Surat</th><th>Kegiatan</th><th>Lokasi</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    @forelse($latestLetters as $letter)
                        <tr><td>{{ $loop->iteration }}</td><td class="font-medium">{{ $letter->number ?: 'Draft #'.$letter->id }}</td><td>{{ $letter->subject ?: $letter->activityType?->name ?: '-' }}</td><td>{{ $letter->location ?: '-' }}</td><td class="whitespace-nowrap">{{ $letter->letter_date?->translatedFormat('d M Y') ?: '-' }}</td><td><x-app.status-badge :status="$letter->status" /></td><td>@can('letters.view')<a href="{{ route('letters.show', $letter) }}" wire:navigate class="icon-button" aria-label="Lihat SPT {{ $letter->number ?: $letter->id }}"><x-app.icon name="arrow" /></a>@else<span class="text-slate-400">-</span>@endcan</td></tr>
                    @empty<tr><td colspan="7" class="dashboard-empty"><x-app.icon name="document" class="mx-auto mb-3 size-9 text-blue-300" /><p>Belum ada SPT yang dicatat.</p></td></tr>@endforelse
                </tbody></table>
            </div>
        </section>
        <div class="dashboard-side">
            <section class="dashboard-card">
                <header class="dashboard-card-heading"><h2><x-app.icon class="text-blue-600" /> Buat Surat &amp; Rekap</h2></header>
                <div class="dashboard-shortcuts">
                    @can('letters.create')<a href="{{ route('letters.create') }}" wire:navigate><span class="stat-icon"><x-app.icon /></span><strong>SPT Baru</strong><span>Buat surat tugas</span><x-app.icon name="arrow" class="shortcut-arrow" /></a>@endcan
                    @can('reports.view')<a href="{{ route('spt-recap.index') }}" wire:navigate><span class="stat-icon violet"><x-app.icon name="calendar" /></span><strong>Rekap SPT</strong><span>Ringkasan tugas</span><x-app.icon name="arrow" class="shortcut-arrow" /></a><a href="{{ route('personnel-recap.index') }}" wire:navigate><span class="stat-icon green"><x-app.icon name="users" /></span><strong>Rekap Personil</strong><span>Riwayat personil</span><x-app.icon name="arrow" class="shortcut-arrow" /></a>@endcan
                    @unless(auth()->user()->can('letters.create') || auth()->user()->can('reports.view'))<p class="detail-empty">Akses menu tersedia sesuai hak pengguna.</p>@endunless
                </div>
            </section>
            <section class="dashboard-card">
                <header class="dashboard-card-heading"><h2><x-app.icon name="calendar" class="text-blue-600" /> Jadwal Tugas / SPT</h2>@can('letters.view')<a href="{{ route('letters.index') }}" wire:navigate>Lihat Semua <x-app.icon name="arrow" /></a>@endcan</header>
                <div class="schedule-list">
                    @forelse($scheduledLetters as $letter)
                        <a href="{{ route('letters.show', $letter) }}" wire:navigate class="schedule-item"><span class="detail-icon"><x-app.icon name="calendar" /></span><span class="min-w-0 flex-1"><strong>{{ $letter->subject ?: $letter->activityType?->name }}</strong><span class="schedule-date">{{ $letter->start_date?->translatedFormat('d M') }} &ndash; {{ ($letter->end_date ?? $letter->start_date)?->translatedFormat('d M Y') }}</span></span><span class="status-badge {{ $letter->start_date?->isFuture() ? 'status-warning' : 'status-info' }}">{{ $letter->start_date?->isFuture() ? 'Akan Datang' : 'Berlangsung' }}</span><span class="text-slate-400" aria-hidden="true">&rsaquo;</span></a>
                    @empty<p class="detail-empty">{{ auth()->user()->can('letters.view') ? 'Belum ada jadwal tugas mendatang.' : 'Jadwal tersedia untuk pengguna dengan akses SPT.' }}</p>@endforelse
                </div>
            </section>
        </div>
    </div>
    <div class="dashboard-bottom">
        <section class="dashboard-card">
            <header class="dashboard-card-heading"><h2><x-app.icon name="clock" class="text-blue-600" /> Aktivitas Terakhir</h2>@can('audit-logs.view')<a href="{{ route('audit-logs.index') }}" wire:navigate>Lihat Semua <x-app.icon name="arrow" /></a>@endcan</header>
            <div class="activity-list">
                @forelse($recentActivities as $log)
                    @php($actionLabel = ['CREATE' => 'menambahkan', 'UPDATE' => 'memperbarui', 'PUBLISH' => 'menerbitkan', 'CANCEL' => 'membatalkan', 'DELETE' => 'menghapus', 'MERGE' => 'menggabungkan'][$log->action] ?? strtolower($log->action))
                    @php($subjectLabel = ['Letter' => 'SPT', 'Personnel' => 'personil', 'Unit' => 'unit', 'ActivityType' => 'jenis kegiatan', 'LetterType' => 'jenis surat'][class_basename($log->subject_type)] ?? 'data')
                    <div class="activity-item"><span class="activity-dot {{ $loop->even ? 'bg-emerald-500' : 'bg-blue-500' }}"></span><p>{{ $log->user?->name ?: 'Sistem' }} {{ $actionLabel }} {{ $subjectLabel }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}</p><time>{{ $log->created_at?->translatedFormat('d M Y, H:i') }}</time></div>
                @empty<p class="detail-empty">{{ auth()->user()->can('audit-logs.view') ? 'Belum ada aktivitas tercatat.' : 'Riwayat aktivitas memerlukan akses Log Aktivitas.' }}</p>@endforelse
            </div>
        </section>
        <section class="dashboard-card">
            <header class="dashboard-card-heading"><h2><x-app.icon name="shield" class="text-blue-600" /> Administrasi &amp; Sistem</h2><a href="{{ route('profile.edit') }}" wire:navigate>Pengaturan <x-app.icon name="arrow" /></a></header>
            <div class="system-summary">
                <div><span class="detail-icon green"><x-app.icon name="shield" /></span><div><strong>Akses Pengguna</strong><p>{{ auth()->user()->getRoleNames()->first() ?: 'Pengguna' }}</p></div></div>
                <div><span class="detail-icon"><x-app.icon name="users" /></span><div><strong>Personil Aktif</strong><p>{{ $activePersonnel }} personil</p></div></div>
                <div><span class="detail-icon violet"><x-app.icon /></span><div><strong>Draft SPT</strong><p>{{ $draftSpt }} dalam penyusunan</p></div></div>
                <div><span class="detail-icon green"><x-app.icon name="clock" /></span><div><strong>Log Aktivitas</strong><p>{{ auth()->user()->can('audit-logs.view') ? 'Riwayat dapat ditinjau' : 'Akses terbatas' }}</p></div></div>
            </div>
        </section>
    </div>
</div>
