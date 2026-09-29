<x-app.detail-drawer title="Detail SPT">
    <section class="drawer-section">
        <div class="flex flex-wrap gap-2">
            <x-app.status-badge :status="$selectedLetter->status" />
            <span class="status-badge {{ $selectedLetter->source === 'import' ? 'status-warning' : 'status-info' }}">{{ $selectedLetter->source === 'import' ? 'Import Arsip' : 'Dibuat dari Sistem' }}</span>
            @if($selectedLetter->record_type?->value === 'attendance_correction')<span class="status-badge status-violet">Koreksi Absensi</span>@endif
        </div>
        <h3 class="mt-4 text-xl font-bold leading-snug">{{ $selectedLetter->number ?: 'Draft SPT #'.$selectedLetter->id }}</h3>
        <p class="mt-1 text-xs text-slate-500">Surat Perintah Tugas</p>
        <dl class="detail-fields mt-6">
            <x-app.detail-row label="Tanggal SPT" icon="calendar">{{ $selectedLetter->letter_date?->translatedFormat('d F Y') ?: '-' }}</x-app.detail-row>
            <x-app.detail-row label="Periode Kegiatan" icon="calendar">{{ $selectedLetter->start_date?->translatedFormat('d M Y') ?: '-' }} &ndash; {{ $selectedLetter->end_date?->translatedFormat('d M Y') ?: '-' }}</x-app.detail-row>
            <x-app.detail-row label="Kegiatan">{{ $selectedLetter->subject ?: $selectedLetter->activityType?->name ?: '-' }}</x-app.detail-row>
            <x-app.detail-row label="Lokasi" icon="pin">{{ $selectedLetter->location ?: '-' }}</x-app.detail-row>
            <x-app.detail-row label="Unit / Tim" icon="users">{{ $selectedLetter->personnels->pluck('unit.name')->filter()->unique()->implode(', ') ?: '-' }}</x-app.detail-row>
            <x-app.detail-row label="Jenis Kegiatan" icon="list">{{ $selectedLetter->activityType?->name ?: '-' }}</x-app.detail-row>
            <x-app.detail-row label="Keterangan"><span class="text-slate-500">{{ $selectedLetter->description ?: '-' }}</span></x-app.detail-row>
        </dl>
    </section>
    <section class="drawer-section">
        <h3 class="drawer-section-title">Personil ({{ $selectedLetter->personnels->count() }} orang)</h3>
        <div class="detail-table-wrap">
            <table class="detail-table"><thead><tr><th>No</th><th>Nama</th><th>Jabatan</th><th>Unit/Tim</th></tr></thead>
                <tbody>@forelse($selectedLetter->personnels as $person)
                    <tr><td>{{ $loop->iteration }}</td><td class="font-medium">{{ $person->name }}</td><td>{{ $person->position ?: '-' }}</td><td>{{ $person->unit?->name ?: '-' }}</td></tr>
                @empty<tr><td colspan="4" class="detail-empty">Belum ada personil.</td></tr>@endforelse</tbody>
            </table>
        </div>
    </section>
    <section class="drawer-section">
        <h3 class="drawer-section-title">Dokumen</h3>
        <div class="space-y-3">
            @forelse($selectedLetter->attachments as $attachment)
                <div class="document-card">
                    <x-app.icon name="document" class="size-7 shrink-0 text-red-500" />
                    <div class="min-w-0 flex-1"><p class="break-words text-xs font-semibold">{{ $attachment->original_name }}</p><p class="mt-1 text-xs text-slate-500">{{ strtoupper(pathinfo($attachment->original_name, PATHINFO_EXTENSION)) ?: 'Dokumen' }} &middot; {{ number_format($attachment->size / 1024, 0, ',', '.') }} KB</p></div>
                    <button type="button" wire:click="downloadAttachment({{ $attachment->id }})" wire:loading.attr="disabled" class="icon-button" aria-label="Unduh {{ $attachment->original_name }}"><x-app.icon name="download" /></button>
                </div>
            @empty<p class="detail-empty">Belum ada dokumen terlampir.</p>@endforelse
        </div>
        @error('download')<p role="alert" class="mt-3 text-xs text-red-600">{{ $message }}</p>@enderror
    </section>
    <x-slot:footer>
        @if($selectedLetter->status === \App\Enums\LetterStatus::Draft)
            @can('letters.update')<a href="{{ route('letters.edit', $selectedLetter) }}" wire:navigate class="detail-button detail-button-soft"><x-app.icon name="edit" /> Edit SPT</a>@endcan
        @endif
        @if($selectedLetter->attachments->isNotEmpty())
            <button type="button" wire:click="downloadAttachment({{ $selectedLetter->attachments->first()->id }})" wire:loading.attr="disabled" class="detail-button detail-button-primary"><x-app.icon name="download" /> Download</button>
        @else
            <a href="{{ route('letters.show', $selectedLetter) }}" wire:navigate class="detail-button detail-button-primary"><x-app.icon name="document" /> Buka SPT</a>
        @endif
    </x-slot:footer>
</x-app.detail-drawer>
