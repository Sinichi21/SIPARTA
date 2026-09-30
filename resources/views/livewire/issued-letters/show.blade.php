<div class="space-y-6">
    <x-app.page-heading
        title="Detail Surat Terbit"
        :description="$letter->number"
    >
        <x-slot:actions>
            @if($letter->hasArchivedPdf())
                <button
                    type="button"
                    wire:click="downloadArchivedPdf"
                    class="spt-action spt-action-primary"
                >
                    <x-app.icon name="download" />
                    Unduh PDF Resmi
                </button>
            @endif

            <a
                href="{{ route('issued-letters.index') }}"
                wire:navigate
                class="spt-action spt-action-back"
            >
                Kembali
            </a>

            <a
                href="{{ route('outgoing-letters.show', $letter->outgoingLetter) }}"
                wire:navigate
                class="spt-action spt-action-view"
            >
                Lihat Surat Keluar
            </a>
        </x-slot:actions>
    </x-app.page-heading>

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="portal-card">
            <div class="mb-5 flex flex-wrap items-center gap-3">
                <span class="status-badge status-success">Terbit</span>

                @if($letter->hasArchivedPdf())
                    <span class="status-badge status-info">PDF Diarsipkan</span>
                @endif
            </div>

            <dl class="grid gap-5 md:grid-cols-2">
                <div>
                    <dt class="text-xs text-slate-500">Nomor Surat</dt>
                    <dd class="mt-1 font-semibold">{{ $letter->number }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Tanggal Surat</dt>
                    <dd class="mt-1">{{ $letter->letter_date?->translatedFormat('d F Y') }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Jenis Surat</dt>
                    <dd class="mt-1">{{ $letter->letterType?->name ?: '-' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Tujuan</dt>
                    <dd class="mt-1">{{ $letter->recipient }}</dd>
                </div>

                <div class="md:col-span-2">
                    <dt class="text-xs text-slate-500">Perihal</dt>
                    <dd class="mt-1 font-semibold">{{ $letter->subject }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Penandatangan</dt>
                    <dd class="mt-1">{{ $letter->signatory_name ?: '-' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Jabatan</dt>
                    <dd class="mt-1">{{ $letter->signatory_position ?: '-' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">NIP</dt>
                    <dd class="mt-1">{{ $letter->signatory_nip ?: '-' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Waktu Terbit</dt>
                    <dd class="mt-1">{{ $letter->issued_at?->translatedFormat('d M Y, H:i') }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Diterbitkan oleh</dt>
                    <dd class="mt-1">{{ $letter->issuer?->name ?: '-' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Arsip Dokumen</dt>
                    <dd class="mt-1">
                        {{ $letter->archived_document_at?->translatedFormat('d M Y, H:i') ?: 'Belum tersedia' }}
                    </dd>
                </div>
            </dl>
        </section>


        <section class="portal-card">
            <h2 class="font-semibold">Verifikasi Publik</h2>

            <div class="mt-4 flex items-start gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-2">
                    <img
                        src="{{ $qrDataUri }}"
                        alt="QR verifikasi surat"
                        class="size-28"
                    >
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm text-slate-600">
                        Scan QR untuk membuka halaman verifikasi publik dokumen ini.
                    </p>

                    <a
                        href="{{ $verificationUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-3 inline-flex text-sm font-semibold text-blue-700 hover:underline"
                    >
                        Buka Halaman Verifikasi
                    </a>

                    <dl class="mt-4 grid gap-3 text-xs">
                        <div>
                            <dt class="text-slate-500">Kode Verifikasi</dt>
                            <dd class="mt-1 break-all font-mono text-[11px] text-slate-800">
                                {{ $letter->verification_code }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-slate-500">Jumlah Verifikasi</dt>
                            <dd class="mt-1 font-semibold">
                                {{ number_format($letter->verification_count, 0, ',', '.') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <section class="portal-card">
            <h2 class="font-semibold">Integritas Dokumen</h2>

            @if($letter->checksum_sha256)
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-start gap-3">
                        <span class="stat-icon">
                            <x-app.icon name="shield" />
                        </span>

                        <div class="min-w-0">
                            <strong class="block text-sm text-emerald-900">
                                Snapshot Terverifikasi
                            </strong>
                            <p class="mt-1 text-xs leading-5 text-emerald-800">
                                Data final surat disimpan saat penerbitan dan diberi checksum SHA-256.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <span class="text-xs font-medium text-slate-500">SHA-256</span>
                    <code class="mt-2 block break-all rounded-lg bg-slate-950 p-3 text-[11px] leading-5 text-slate-100">
                        {{ $letter->checksum_sha256 }}
                    </code>
                </div>
            @else
                <x-app.empty-state
                    title="Snapshot belum tersedia"
                    description="Surat lama tetap dapat dibaca, tetapi belum memiliki arsip PDF/checksum Phase 14."
                    icon="shield"
                />
            @endif

            @if($letter->hasArchivedPdf())
                <button
                    type="button"
                    wire:click="downloadArchivedPdf"
                    class="spt-action spt-action-primary mt-5 w-full justify-center"
                >
                    <x-app.icon name="download" />
                    Unduh PDF Resmi
                </button>
            @endif
        </section>
    </div>

    @if($letter->snapshot_json)
        <section class="portal-card">
            <div class="mb-4">
                <h2 class="font-semibold">Snapshot Penerbitan</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan data yang dikunci pada saat surat diterbitkan.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 p-4">
                    <span class="text-xs text-slate-500">Template</span>
                    <strong class="mt-1 block text-sm">
                        {{ data_get($letter->snapshot_json, 'template.name') ?: '-' }}
                    </strong>
                    <span class="mt-1 block text-xs text-slate-500">
                        Versi {{ data_get($letter->snapshot_json, 'template.version') ?: '-' }}
                    </span>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <span class="text-xs text-slate-500">Profil Kop</span>
                    <strong class="mt-1 block text-sm">
                        {{ data_get($letter->snapshot_json, 'letterhead.name') ?: '-' }}
                    </strong>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <span class="text-xs text-slate-500">Status Snapshot</span>
                    <strong class="mt-1 block text-sm">
                        {{ ucfirst((string) data_get($letter->snapshot_json, 'status', 'active')) }}
                    </strong>
                </div>
            </div>
        </section>
    @endif
</div>
