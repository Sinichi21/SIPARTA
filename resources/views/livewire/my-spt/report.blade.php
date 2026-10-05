<div class="spt-detail-page">
    <nav aria-label="Breadcrumb" class="spt-breadcrumb">
        <a href="{{ route('my-spt.index') }}" wire:navigate>SPT Saya</a>
        <span>/</span>
        <a href="{{ route('my-spt.show', $letter) }}" wire:navigate>Detail SPT</a>
        <span>/</span>
        <span>Laporan SKP</span>
    </nav>

    <x-app.page-heading
        title="Laporan SKP"
        description="Satu laporan mewakili seluruh peserta pada SPT yang sama."
    >
        <x-slot:actions>
            <a href="{{ route('my-spt.show', $letter) }}" wire:navigate class="spt-action spt-action-back">
                Kembali ke SPT
            </a>
        </x-slot:actions>
    </x-app.page-heading>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @error('report')
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ $message }}
        </div>
    @enderror

    <section class="spt-summary-card">
        <div class="spt-summary-title">
            <span class="spt-document-icon"><x-app.icon name="document" /></span>
            <div class="min-w-0">
                <div class="mb-3 flex flex-wrap gap-2">
                    <span class="status-badge {{ $report?->isSubmitted() ? 'status-success' : ($report ? 'status-warning' : 'status-info') }}">
                        {{ $report?->isSubmitted() ? 'Sudah Dilaporkan' : ($report ? 'Draft Laporan' : 'Belum Dilaporkan') }}
                    </span>
                </div>
                <h2>{{ $letter->number ?: 'SPT #'.$letter->id }}</h2>
                <p>{{ $letter->subject ?: $letter->activityType?->name ?: '-' }}</p>
            </div>
        </div>

        <div class="spt-summary-metrics">
            <div><x-app.icon name="calendar" /><span>Periode<strong>{{ $letter->start_date?->translatedFormat('d M Y') ?: '-' }}@if($letter->end_date && ! $letter->end_date->equalTo($letter->start_date)) – {{ $letter->end_date->translatedFormat('d M Y') }}@endif</strong></span></div>
            <div><x-app.icon name="pin" /><span>Lokasi<strong>{{ $letter->location ?: '-' }}</strong></span></div>
            <div><x-app.icon name="users" /><span>Pelapor<strong>{{ $report?->creator?->name ?: 'Belum ada' }}</strong></span></div>
        </div>
    </section>

    @if($report && !$this->canEdit)
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
            @if($report->isSubmitted())
                Laporan ini sudah dikirim oleh {{ $report->submitter?->name ?: $report->creator?->name }} dan berlaku untuk seluruh peserta SPT.
            @else
                Draft laporan sedang disusun oleh {{ $report->creator?->name }}. Peserta lain tidak perlu membuat laporan baru.
            @endif
        </div>
    @endif

    <section class="portal-card">
        <header class="portal-card-heading">
            <h2><x-app.icon name="list" /> Isi Laporan</h2>
            <span class="portal-caption">Pelaksanaan dan hasil kegiatan</span>
        </header>

        <div class="grid gap-5">
            <label class="grid gap-1.5 text-sm font-medium">
                <span>Ringkasan Pelaksanaan *</span>
                <textarea wire:model="activity_summary" rows="5" @disabled(!$this->canEdit)></textarea>
                @error('activity_summary')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="grid gap-1.5 text-sm font-medium">
                <span>Hasil / Capaian *</span>
                <textarea wire:model="results" rows="5" @disabled(!$this->canEdit)></textarea>
                @error('results')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="grid gap-1.5 text-sm font-medium">
                <span>Kendala</span>
                <textarea wire:model="obstacles" rows="4" @disabled(!$this->canEdit)></textarea>
                @error('obstacles')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="grid gap-1.5 text-sm font-medium">
                <span>Tindak Lanjut</span>
                <textarea wire:model="follow_up" rows="4" @disabled(!$this->canEdit)></textarea>
                @error('follow_up')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
        </div>

        <div class="mt-6 border-t border-slate-200 pt-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-semibold text-slate-900">Dokumen Pendukung</h3>
                    <p class="mt-1 text-xs text-slate-500">PDF privat, maksimal 5 file dan 10 MB per file.</p>
                </div>
            </div>

            @error('attachments')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
            @error('attachments.*')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror

            @if($this->canEdit)
                <div class="mt-4 rounded-xl border border-dashed border-slate-300 p-4">
                    @if(!$report)
                        <p class="text-sm text-amber-700">Simpan draft laporan terlebih dahulu sebelum mengunggah dokumen pendukung.</p>
                    @else
                        <input
                            type="file"
                            wire:model="attachments"
                            multiple
                            accept="application/pdf,.pdf"
                            class="block w-full text-sm"
                        >
                        <div class="mt-3 flex justify-end">
                            <button
                                type="button"
                                wire:click="uploadAttachments"
                                wire:loading.attr="disabled"
                                wire:target="attachments,uploadAttachments"
                                class="spt-action spt-action-back"
                            >
                                <span wire:loading.remove wire:target="uploadAttachments">Unggah PDF</span>
                                <span wire:loading wire:target="uploadAttachments">Mengunggah...</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-4 grid gap-3">
                @forelse($report?->attachments ?? collect() as $attachment)
                    <div class="flex flex-col gap-3 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <strong class="block truncate text-sm">{{ $attachment->original_name }}</strong>
                            <span class="mt-1 block text-xs text-slate-500">
                                {{ number_format($attachment->size / 1024, 1, ',', '.') }} KB
                                · diunggah oleh {{ $attachment->uploader?->name ?: '-' }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a
                                href="{{ route('my-spt-report-attachments.download', $attachment) }}"
                                class="spt-action spt-action-view"
                            >
                                Unduh
                            </a>
                            @if($this->canEdit)
                                <button
                                    type="button"
                                    wire:click="deleteAttachment({{ $attachment->id }})"
                                    wire:confirm="Hapus dokumen pendukung ini?"
                                    class="spt-action spt-action-back"
                                >
                                    Hapus
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500">
                        Belum ada dokumen pendukung.
                    </div>
                @endforelse
            </div>
        </div>

        @if($this->canEdit)
            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" wire:click="saveDraft" class="spt-action spt-action-back">Simpan Draft</button>
                <button type="button" wire:click="submit" class="spt-action spt-action-primary">Kirim Laporan</button>
            </div>
        @endif
    </section>
</div>
