<div class="dashboard-page">
    <div class="dashboard-welcome">
        <div>
            <h1 class="font-bold">Selamat Datang, {{ auth()->user()->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">
                Kelola persuratan, workflow penerbitan, dan SPT dari satu dashboard.
            </p>
        </div>

        <div class="dashboard-date">
            <p>
                {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                <x-app.icon name="sun" class="text-amber-500" />
            </p>
            <span>Tetap semangat dalam memberikan pelayanan terbaik.</span>
        </div>
    </div>

    @canany(['incoming-letters.view', 'outgoing-letters.view', 'issued-letters.view'])
        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900">Ringkasan Persuratan</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Kondisi workflow surat yang membutuhkan perhatian.
                    </p>
                </div>

                @can('reports.view')
                    <a
                        href="{{ route('correspondence-register.index') }}"
                        wire:navigate
                        class="spt-action spt-action-view"
                    >
                        Register Persuratan
                        <x-app.icon name="arrow" />
                    </a>
                @endcan
            </div>

            <div class="dashboard-stats">
                @can('incoming-letters.view')
                    <x-app.stat-card
                        label="Surat Masuk Aktif"
                        :value="number_format($incomingActive, 0, ',', '.')"
                        description="Dicatat / disposisi / proses"
                        icon="document"
                    />
                @endcan

                @can('outgoing-letters.view')
                    <x-app.stat-card
                        label="Surat Keluar Diproses"
                        :value="number_format($outgoingPending, 0, ',', '.')"
                        description="Draft sampai menunggu publish"
                        icon="clock"
                        tone="violet"
                    />
                @endcan

                @can('issued-letters.view')
                    <x-app.stat-card
                        label="Terbit Bulan Ini"
                        :value="number_format($issuedThisMonth, 0, ',', '.')"
                        :description="now()->locale('id')->translatedFormat('F Y')"
                        icon="shield"
                        tone="green"
                    />

                    <x-app.stat-card
                        label="Dokumen Dicabut"
                        :value="number_format($revokedLetters, 0, ',', '.')"
                        description="Tetap tersimpan sebagai histori"
                        icon="archive"
                        tone="amber"
                    />
                @endcan
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            @can('incoming-letters.view')
                <section class="dashboard-card">
                    <header class="dashboard-card-heading">
                        <h2>
                            <x-app.icon name="document" class="text-blue-600" />
                            Antrian Surat Masuk
                        </h2>
                        <a href="{{ route('incoming-letters.index') }}" wire:navigate>
                            Lihat Semua <x-app.icon name="arrow" />
                        </a>
                    </header>

                    <div class="schedule-list">
                        @forelse($incomingQueue as $letter)
                            <a
                                href="{{ route('incoming-letters.show', $letter) }}"
                                wire:navigate
                                class="schedule-item"
                            >
                                <span class="detail-icon">
                                    <x-app.icon name="document" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <strong>{{ $letter->subject }}</strong>
                                    <span class="schedule-date">
                                        {{ $letter->sender }} ·
                                        {{ $letter->received_date?->translatedFormat('d M Y') }}
                                    </span>
                                </span>

                                <span class="status-badge status-info">
                                    {{ $letter->status->label() }}
                                </span>
                            </a>
                        @empty
                            <p class="detail-empty">Tidak ada surat masuk aktif.</p>
                        @endforelse
                    </div>
                </section>
            @endcan

            @can('outgoing-letters.view')
                <section class="dashboard-card">
                    <header class="dashboard-card-heading">
                        <h2>
                            <x-app.icon name="clock" class="text-violet-600" />
                            Workflow Surat Keluar
                        </h2>
                        <a href="{{ route('outgoing-letters.index') }}" wire:navigate>
                            Lihat Semua <x-app.icon name="arrow" />
                        </a>
                    </header>

                    <div class="schedule-list">
                        @forelse($outgoingQueue as $letter)
                            <a
                                href="{{ route('outgoing-letters.show', $letter) }}"
                                wire:navigate
                                class="schedule-item"
                            >
                                <span class="detail-icon violet">
                                    <x-app.icon name="document" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <strong>{{ $letter->subject }}</strong>
                                    <span class="schedule-date">
                                        {{ $letter->letterType?->name ?: 'Surat Keluar' }}
                                        · {{ $letter->recipient }}
                                    </span>
                                </span>

                                <span class="status-badge status-info">
                                    {{ $letter->status->label() }}
                                </span>
                            </a>
                        @empty
                            <p class="detail-empty">Tidak ada surat keluar dalam proses.</p>
                        @endforelse
                    </div>
                </section>
            @endcan

            @can('issued-letters.view')
                <section class="dashboard-card">
                    <header class="dashboard-card-heading">
                        <h2>
                            <x-app.icon name="shield" class="text-emerald-600" />
                            Surat Terbit Terbaru
                        </h2>
                        <a href="{{ route('issued-letters.index') }}" wire:navigate>
                            Lihat Semua <x-app.icon name="arrow" />
                        </a>
                    </header>

                    <div class="schedule-list">
                        @forelse($recentIssued as $letter)
                            <a
                                href="{{ route('issued-letters.show', $letter) }}"
                                wire:navigate
                                class="schedule-item"
                            >
                                <span class="detail-icon green">
                                    <x-app.icon name="shield" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <strong>{{ $letter->number }}</strong>
                                    <span class="schedule-date">
                                        {{ $letter->subject }}
                                    </span>
                                </span>

                                <span class="status-badge {{ $letter->status === 'revoked' ? 'status-danger' : 'status-success' }}">
                                    {{ $letter->status === 'revoked' ? 'Dicabut' : 'Aktif' }}
                                </span>
                            </a>
                        @empty
                            <p class="detail-empty">Belum ada surat yang diterbitkan.</p>
                        @endforelse
                    </div>
                </section>
            @endcan
        </div>
    @endcanany

    <section class="space-y-4">
        <div>
            <h2 class="font-bold text-slate-900">Ringkasan SPT</h2>
            <p class="mt-1 text-sm text-slate-500">
                Statistik dan aktivitas Surat Perintah Tugas.
            </p>
        </div>

        <div class="dashboard-stats">
            @foreach ([
                ['Total SPT', $totalSpt, 'Seluruh surat perintah tugas', 'document', ''],
                ['Diterbitkan', $publishedSpt, 'SPT yang telah diterbitkan', 'document', 'green'],
                ['SPT Bulan Ini', $monthlySpt, now()->locale('id')->translatedFormat('F Y'), 'calendar', 'violet'],
                ['Arsip SPT', $archivedSpt, 'Surat yang telah diarsipkan', 'archive', 'amber'],
            ] as [$label, $value, $description, $icon, $tone])
                <x-app.stat-card
                    :label="$label"
                    :value="number_format($value, 0, ',', '.')"
                    :description="$description"
                    :icon="$icon"
                    :tone="$tone"
                />
            @endforeach
        </div>
    </section>

    <div class="dashboard-main">
        <section class="dashboard-card dashboard-letters">
            <header class="dashboard-card-heading">
                <h2>
                    <x-app.icon class="text-blue-600" />
                    SPT Terbaru
                </h2>

                @can('letters.view')
                    <a href="{{ route('letters.index') }}" wire:navigate>
                        Lihat Semua <x-app.icon name="arrow" />
                    </a>
                @endcan
            </header>

            <div class="dashboard-table-scroll">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No Surat</th>
                            <th>Kegiatan</th>
                            <th>Lokasi</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestLetters as $letter)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="font-medium">
                                    {{ $letter->number ?: 'Draft #'.$letter->id }}
                                </td>
                                <td>
                                    {{ $letter->subject ?: $letter->activityType?->name ?: '-' }}
                                </td>
                                <td>{{ $letter->location ?: '-' }}</td>
                                <td class="whitespace-nowrap">
                                    {{ $letter->letter_date?->translatedFormat('d M Y') ?: '-' }}
                                </td>
                                <td>
                                    <x-app.status-badge :status="$letter->status" />
                                </td>
                                <td>
                                    @can('letters.view')
                                        <a
                                            href="{{ route('letters.show', $letter) }}"
                                            wire:navigate
                                            class="icon-button"
                                            aria-label="Lihat SPT {{ $letter->number ?: $letter->id }}"
                                        >
                                            <x-app.icon name="arrow" />
                                        </a>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="dashboard-empty">
                                    <x-app.icon name="document" class="mx-auto mb-3 size-9 text-blue-300" />
                                    <p>Belum ada SPT yang dicatat.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="dashboard-side">
            <section class="dashboard-card">
                <header class="dashboard-card-heading">
                    <h2>
                        <x-app.icon class="text-blue-600" />
                        Akses Cepat
                    </h2>
                </header>

                <div class="dashboard-shortcuts">
                    @can('incoming-letters.create')
                        <a href="{{ route('incoming-letters.create') }}" wire:navigate>
                            <span class="stat-icon">
                                <x-app.icon name="document" />
                            </span>
                            <strong>Catat Surat Masuk</strong>
                            <span>Registrasi surat diterima</span>
                            <x-app.icon name="arrow" class="shortcut-arrow" />
                        </a>
                    @endcan

                    @can('outgoing-letters.create')
                        <a href="{{ route('outgoing-letters.create') }}" wire:navigate>
                            <span class="stat-icon violet">
                                <x-app.icon name="document" />
                            </span>
                            <strong>Surat Keluar Baru</strong>
                            <span>Susun draft surat</span>
                            <x-app.icon name="arrow" class="shortcut-arrow" />
                        </a>
                    @endcan

                    @can('letters.create')
                        <a href="{{ route('letters.create') }}" wire:navigate>
                            <span class="stat-icon green">
                                <x-app.icon name="document" />
                            </span>
                            <strong>SPT Baru</strong>
                            <span>Buat data penugasan</span>
                            <x-app.icon name="arrow" class="shortcut-arrow" />
                        </a>
                    @endcan

                    @can('reports.view')
                        <a href="{{ route('correspondence-register.index') }}" wire:navigate>
                            <span class="stat-icon violet">
                                <x-app.icon name="chart" />
                            </span>
                            <strong>Register Persuratan</strong>
                            <span>Rekap masuk, keluar, terbit</span>
                            <x-app.icon name="arrow" class="shortcut-arrow" />
                        </a>
                    @endcan
                </div>
            </section>

            <section class="dashboard-card">
                <header class="dashboard-card-heading">
                    <h2>
                        <x-app.icon name="calendar" class="text-blue-600" />
                        Jadwal Tugas / SPT
                    </h2>

                    @can('letters.view')
                        <a href="{{ route('letters.index') }}" wire:navigate>
                            Lihat Semua <x-app.icon name="arrow" />
                        </a>
                    @endcan
                </header>

                <div class="schedule-list">
                    @forelse($scheduledLetters as $letter)
                        <a
                            href="{{ route('letters.show', $letter) }}"
                            wire:navigate
                            class="schedule-item"
                        >
                            <span class="detail-icon">
                                <x-app.icon name="calendar" />
                            </span>

                            <span class="min-w-0 flex-1">
                                <strong>
                                    {{ $letter->subject ?: $letter->activityType?->name }}
                                </strong>
                                <span class="schedule-date">
                                    {{ $letter->start_date?->translatedFormat('d M') }}
                                    &ndash;
                                    {{ ($letter->end_date ?? $letter->start_date)?->translatedFormat('d M Y') }}
                                </span>
                            </span>

                            <span class="status-badge {{ $letter->start_date?->isFuture() ? 'status-warning' : 'status-info' }}">
                                {{ $letter->start_date?->isFuture() ? 'Akan Datang' : 'Berlangsung' }}
                            </span>
                        </a>
                    @empty
                        <p class="detail-empty">
                            {{ auth()->user()->can('letters.view')
                                ? 'Belum ada jadwal tugas mendatang.'
                                : 'Jadwal tersedia untuk pengguna dengan akses SPT.' }}
                        </p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <div class="dashboard-bottom">
        <section class="dashboard-card">
            <header class="dashboard-card-heading">
                <h2>
                    <x-app.icon name="clock" class="text-blue-600" />
                    Aktivitas Terakhir
                </h2>

                @can('audit-logs.view')
                    <a href="{{ route('audit-logs.index') }}" wire:navigate>
                        Lihat Semua <x-app.icon name="arrow" />
                    </a>
                @endcan
            </header>

            <div class="activity-list">
                @forelse($recentActivities as $log)
                    @php($actionLabel = [
                        'CREATE' => 'menambahkan',
                        'UPDATE' => 'memperbarui',
                        'PUBLISH' => 'menerbitkan',
                        'CANCEL' => 'membatalkan',
                        'DELETE' => 'menghapus',
                        'MERGE' => 'menggabungkan',
                    ][$log->action] ?? strtolower($log->action))

                    @php($subjectLabel = [
                        'Letter' => 'SPT',
                        'IncomingLetter' => 'surat masuk',
                        'OutgoingLetter' => 'surat keluar',
                        'IssuedLetter' => 'surat terbit',
                        'Personnel' => 'personil',
                        'Unit' => 'unit',
                        'ActivityType' => 'jenis kegiatan',
                        'LetterType' => 'jenis surat',
                    ][class_basename($log->subject_type)] ?? 'data')

                    <div class="activity-item">
                        <span class="activity-dot {{ $loop->even ? 'bg-emerald-500' : 'bg-blue-500' }}"></span>
                        <p>
                            {{ $log->user?->name ?: 'Sistem' }}
                            {{ $actionLabel }}
                            {{ $subjectLabel }}
                            {{ $log->subject_id ? '#'.$log->subject_id : '' }}
                        </p>
                        <time>{{ $log->created_at?->translatedFormat('d M Y, H:i') }}</time>
                    </div>
                @empty
                    <p class="detail-empty">
                        {{ auth()->user()->can('audit-logs.view')
                            ? 'Belum ada aktivitas tercatat.'
                            : 'Riwayat aktivitas memerlukan akses Log Aktivitas.' }}
                    </p>
                @endforelse
            </div>
        </section>

        <section class="dashboard-card">
            <header class="dashboard-card-heading">
                <h2>
                    <x-app.icon name="shield" class="text-blue-600" />
                    Administrasi & Sistem
                </h2>

                <a href="{{ route('profile.edit') }}" wire:navigate>
                    Pengaturan <x-app.icon name="arrow" />
                </a>
            </header>

            <div class="system-summary">
                <div>
                    <span class="detail-icon green">
                        <x-app.icon name="shield" />
                    </span>
                    <div>
                        <strong>Akses Pengguna</strong>
                        <p>{{ auth()->user()->getRoleNames()->first() ?: 'Pengguna' }}</p>
                    </div>
                </div>

                <div>
                    <span class="detail-icon">
                        <x-app.icon name="users" />
                    </span>
                    <div>
                        <strong>Personil Aktif</strong>
                        <p>{{ $activePersonnel }} personil</p>
                    </div>
                </div>

                <div>
                    <span class="detail-icon violet">
                        <x-app.icon />
                    </span>
                    <div>
                        <strong>Draft SPT</strong>
                        <p>{{ $draftSpt }} dalam penyusunan</p>
                    </div>
                </div>

                <div>
                    <span class="detail-icon green">
                        <x-app.icon name="clock" />
                    </span>
                    <div>
                        <strong>Log Aktivitas</strong>
                        <p>
                            {{ auth()->user()->can('audit-logs.view')
                                ? 'Riwayat dapat ditinjau'
                                : 'Akses terbatas' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
