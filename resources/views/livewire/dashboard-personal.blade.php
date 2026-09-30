<div class="dashboard-page">
    <div class="dashboard-welcome">
        <div>
            <h1 class="font-bold">
                Selamat Datang, {{ auth()->user()->name }}
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Lihat penugasan dan rekap pribadi tanpa menu operasional yang tidak diperlukan.
            </p>
        </div>

        <div class="dashboard-date">
            <p>
                {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                <x-app.icon name="sun" class="text-amber-500" />
            </p>
            <span>Portal pribadi SIPARTA</span>
        </div>
    </div>

    @if(! auth()->user()->personnel_id)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
            Akun Anda belum ditautkan ke data personil. Hubungi administrator agar riwayat SPT dapat ditampilkan.
        </div>
    @endif

    <div class="dashboard-stats">
        @foreach ([
            ['SPT Saya', $personalTotalSpt, 'Penugasan yang terkait langsung dengan Anda', 'document', ''],
            ['Tahun Ini', $personalYearSpt, (string) now()->year, 'calendar', 'violet'],
            ['Diterbitkan', $personalPublishedSpt, 'SPT personal yang telah diterbitkan', 'document', 'green'],
            ['Mendatang', $personalUpcomingSpt, 'Jadwal tugas yang akan datang', 'clock', 'amber'],
        ] as [$label, $value, $description, $icon, $tone])
            <section class="dashboard-stat">
                <div class="stat-icon {{ $tone }}">
                    <x-app.icon :name="$icon" class="size-8" />
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-500">
                        {{ $label }}
                    </p>
                    <strong>{{ number_format($value, 0, ',', '.') }}</strong>
                    <p class="text-xs text-slate-500">
                        {{ $description }}
                    </p>
                </div>
            </section>
        @endforeach
    </div>

    <div class="dashboard-main">
        <section class="dashboard-card dashboard-letters">
            <header class="dashboard-card-heading">
                <h2>
                    <x-app.icon class="text-blue-600" />
                    SPT Saya Terbaru
                </h2>

                @can('my-letters.view')
                    <a href="{{ route('my-spt.index') }}" wire:navigate>
                        Lihat Semua
                        <x-app.icon name="arrow" />
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
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($personalLatestSpt as $letter)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="font-medium">
                                    <a
                                        href="{{ route('my-spt.show', $letter) }}"
                                        wire:navigate
                                        class="text-blue-700"
                                    >
                                        {{ $letter->number ?: 'SPT #'.$letter->id }}
                                    </a>
                                </td>
                                <td>{{ $letter->subject ?: $letter->activityType?->name ?: '-' }}</td>
                                <td>{{ $letter->location ?: '-' }}</td>
                                <td class="whitespace-nowrap">
                                    {{ $letter->letter_date?->translatedFormat('d M Y') ?: '-' }}
                                </td>
                                <td>
                                    <x-app.status-badge :status="$letter->status" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="dashboard-empty">
                                    Belum ada SPT yang terkait langsung dengan akun Anda.
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
                        <x-app.icon name="document" class="text-blue-600" />
                        Administrasi Saya
                    </h2>
                </header>

                <div class="dashboard-shortcuts">
                    @can('my-letters.view')
                        <a href="{{ route('my-spt.index') }}" wire:navigate>
                            <span class="stat-icon">
                                <x-app.icon name="document" />
                            </span>
                            <strong>SPT Saya</strong>
                            <span>Lihat surat tugas yang terkait dengan Anda</span>
                            <x-app.icon name="arrow" class="shortcut-arrow" />
                        </a>
                    @endcan

                    @can('my-reports.view')
                        <a href="{{ route('my-recap.index') }}" wire:navigate>
                            <span class="stat-icon violet">
                                <x-app.icon name="calendar" />
                            </span>
                            <strong>Rekap Saya</strong>
                            <span>Ringkasan riwayat penugasan pribadi</span>
                            <x-app.icon name="arrow" class="shortcut-arrow" />
                        </a>
                    @endcan

                    <a href="{{ route('profile.edit') }}" wire:navigate>
                        <span class="stat-icon green">
                            <x-app.icon name="shield" />
                        </span>
                        <strong>Profil & Keamanan</strong>
                        <span>Password, Authenticator, dan Passkey</span>
                        <x-app.icon name="arrow" class="shortcut-arrow" />
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
