<div class="spt-detail-page">
    <nav aria-label="Breadcrumb" class="spt-breadcrumb"><a href="{{ route('letters.index') }}" wire:navigate>Data SPT</a><span aria-hidden="true">/</span><span aria-current="page">Detail SPT</span></nav>
    @if(session('success'))<div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @error('status')<div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>@enderror

    <header class="spt-detail-heading">
        <div class="min-w-0"><p class="spt-eyebrow">Surat Perintah Tugas</p><h1>Detail SPT</h1><p class="mt-1 text-sm text-slate-500">Informasi surat, penugasan personil, dan dokumen pendukung.</p></div>
        <div class="spt-page-actions">
            <a href="{{ route('letters.index') }}" wire:navigate class="spt-action spt-action-back">Kembali</a>
            <x-letters.edit-action :letter="$letter" show-disabled />
            @if($letter->status === \App\Enums\LetterStatus::Draft)
                @can('letters.publish')<button type="button" wire:click="publish" wire:loading.attr="disabled" wire:confirm="Terbitkan SPT ini? Setelah diterbitkan data tidak dapat diedit langsung." class="spt-action spt-action-primary"><x-app.icon name="shield" /> Terbitkan SPT</button>@endcan
            @endif
        </div>
    </header>

    @if($letter->source === 'import')
        @can('letters.update')<div class="import-correction-warning"><x-app.icon name="shield" /><div><h2>SPT hasil import</h2><p>Data dapat diedit untuk memperbaiki kesalahan import. Pastikan koreksi sesuai dokumen asli karena perubahan memengaruhi rekap dan riwayat penugasan.</p></div></div>@endcan
    @endif
    <section class="spt-summary-card">
        <div class="spt-summary-title"><span class="spt-document-icon"><x-app.icon name="document" /></span><div class="min-w-0"><div class="mb-3 flex flex-wrap gap-2"><x-app.status-badge :status="$letter->status" /><span class="status-badge {{ $letter->source === 'import' ? 'status-warning' : 'status-info' }}">{{ $letter->source === 'import' ? 'Import Arsip' : 'Dibuat dari Sistem' }}</span><span class="status-badge status-neutral">{{ $letter->record_type?->label() ?? 'SPT Normal' }}</span></div><h2>{{ $letter->number ?: 'Draft SPT #'.$letter->id }}</h2><p>{{ $letter->subject }}</p></div></div>
        <div class="spt-summary-metrics">
            <div><x-app.icon name="calendar" /><span>Tanggal SPT<strong>{{ $letter->letter_date?->translatedFormat('d F Y') ?: '-' }}</strong></span></div>
            <div><x-app.icon name="clock" /><span>Periode Penugasan<strong>{{ $letter->start_date?->translatedFormat('d M Y') ?: '-' }} &ndash; {{ $letter->end_date?->translatedFormat('d M Y') ?: '-' }}</strong></span></div>
            <div><x-app.icon name="users" /><span>Personil Ditugaskan<strong>{{ $letter->personnels->count() }} orang</strong></span></div>
        </div>
    </section>

    <div class="spt-detail-grid">
        <div class="spt-detail-main">
            <section class="spt-section-card">
                <h2 class="spt-section-heading"><x-app.icon name="list" /> Informasi Penugasan</h2>
                <dl class="spt-information-grid">
                    <div><dt>Jenis Kegiatan</dt><dd>{{ $letter->activityType?->name ?: '-' }}</dd></div>
                    <div><dt>Lokasi</dt><dd>{{ $letter->location ?: '-' }}</dd></div>
                    <div class="md:col-span-2"><dt>Unit / Tim Kerja</dt><dd>{{ $letter->personnels->pluck('unit.name')->filter()->unique()->implode(', ') ?: '-' }}</dd></div>
                </dl>
                <div class="spt-text-section"><h3>Dasar Penugasan</h3><p>{{ $letter->basis ?: 'Belum ada dasar penugasan yang dicatat.' }}</p></div>
                <div class="spt-text-section"><h3>Keterangan</h3><p>{{ $letter->description ?: 'Tidak ada keterangan tambahan.' }}</p></div>
            </section>
            <section class="spt-section-card !p-0">
                <h2 class="spt-section-heading m-0 px-5 py-4"><x-app.icon name="users" /> Daftar Personil <span class="count-badge">{{ $letter->personnels->count() }} orang</span></h2>
                <div class="overflow-x-auto"><table class="spt-personnel-table"><thead><tr><th>No</th><th>Nama / NIP</th><th>Jabatan</th><th>Unit / Tim</th></tr></thead><tbody>
                    @forelse($letter->personnels as $person)
                        <tr><td>{{ $loop->iteration }}</td><td><strong>{{ $person->name }}</strong><span>{{ $person->nip ?: 'NIP belum tersedia' }}</span></td><td>{{ $person->position ?: '-' }}</td><td>{{ $person->unit?->name ?: '-' }}</td></tr>
                    @empty<tr><td colspan="4" class="detail-empty">Belum ada personil yang ditugaskan.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        </div>
        <div class="spt-detail-side">
            <section class="spt-section-card">
                <h2 class="spt-section-heading"><x-app.icon name="document" /> Dokumen Pendukung <span class="count-badge">{{ $letter->attachments->count() }}</span></h2>
                <div class="space-y-3">@forelse($letter->attachments as $attachment)
                    <div class="document-card"><x-app.icon class="shrink-0 text-red-500" /><div class="min-w-0 flex-1"><p class="break-words text-xs font-semibold">{{ $attachment->original_name }}</p><p class="mt-1 text-xs text-slate-500">{{ strtoupper(pathinfo($attachment->original_name, PATHINFO_EXTENSION)) ?: 'Dokumen' }} &middot; {{ number_format(($attachment->size ?? 0) / 1024, 0, ',', '.') }} KB</p></div><button type="button" wire:click="downloadAttachment({{ $attachment->id }})" wire:loading.attr="disabled" class="icon-button" aria-label="Unduh {{ $attachment->original_name }}"><x-app.icon name="download" /></button></div>
                @empty<div class="spt-document-empty"><x-app.icon name="archive" /><p>Belum ada dokumen terlampir.</p></div>@endforelse</div>
                @error('download')<p role="alert" class="mt-3 text-xs text-red-600">{{ $message }}</p>@enderror
            </section>
            <section class="spt-section-card">
                <h2 class="spt-section-heading"><x-app.icon name="clock" /> Informasi Pencatatan</h2>
                <dl class="spt-audit-fields">
                    <div><dt>Dibuat oleh</dt><dd>{{ $letter->creator?->name ?: '-' }}</dd></div>
                    <div><dt>Dibuat pada</dt><dd>{{ $letter->created_at?->translatedFormat('d M Y, H:i') ?: '-' }}</dd></div>
                    @if($letter->updater)<div><dt>Diperbarui oleh</dt><dd>{{ $letter->updater->name }}</dd></div>@endif
                    @if($letter->updated_at)<div><dt>Pembaruan terakhir</dt><dd>{{ $letter->updated_at->translatedFormat('d M Y, H:i') }}</dd></div>@endif
                    @if($letter->published_at)<div><dt>Diterbitkan pada</dt><dd>{{ $letter->published_at->translatedFormat('d M Y, H:i') }}</dd></div>@endif
                </dl>
                @if(! $letter->canBeEdited())
                    @can('letters.update')<p class="spt-edit-note"><x-app.icon name="shield" /><span>SPT dari sistem hanya dapat diedit saat berstatus draft.</span></p>@endcan
                @endif
            </section>
        </div>
    </div>

    @if($letter->status === \App\Enums\LetterStatus::Cancelled)
        <section class="spt-cancellation-info"><h2 class="font-semibold">SPT Dibatalkan</h2><p class="mt-2 whitespace-pre-line text-sm">{{ $letter->cancellation_reason ?: '-' }}</p><p class="mt-2 text-xs">{{ $letter->canceller?->name ?: '-' }} &middot; {{ $letter->cancelled_at?->translatedFormat('d M Y, H:i') ?: '-' }}</p></section>
    @else
        @can('letters.cancel')
            <details class="spt-cancel-section" @if($errors->has('cancellationReason')) open @endif>
                <summary><span>Batalkan SPT</span><span class="text-xs font-normal">Tampilkan formulir pembatalan</span></summary>
                <div class="p-5"><label for="cancellation-reason" class="text-sm font-medium">Alasan pembatalan</label><textarea id="cancellation-reason" wire:model="cancellationReason" rows="3" placeholder="Jelaskan alasan pembatalan (minimal 5 karakter)..." class="mt-2 w-full"></textarea>@error('cancellationReason')<p role="alert" class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror<button type="button" wire:click="cancel" wire:loading.attr="disabled" wire:confirm="Yakin membatalkan SPT ini?" class="mt-3 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white">Batalkan SPT</button></div>
            </details>
        @endcan
    @endif
</div>
