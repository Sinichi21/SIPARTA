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

        @if($this->canEdit)
            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" wire:click="saveDraft" class="spt-action spt-action-back">Simpan Draft</button>
                <button type="button" wire:click="submit" class="spt-action spt-action-primary">Kirim Laporan</button>
            </div>
        @endif
    </section>
</div>
