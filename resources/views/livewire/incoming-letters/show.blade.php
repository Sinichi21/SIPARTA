<div class="space-y-6">
    <x-app.page-heading title="Detail Surat Masuk" :description="$letter->agenda_number.' · '.$letter->sender">
        <x-slot:actions>
            @if($letter->canBeEdited()) @can('incoming-letters.update')<a href="{{ route('incoming-letters.edit', $letter) }}" wire:navigate class="spt-action spt-action-view">Edit</a>@endcan @endif
            <a href="{{ route('incoming-letters.index') }}" wire:navigate class="spt-action spt-action-back">Kembali</a>
        </x-slot:actions>
    </x-app.page-heading>

    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
        <section class="portal-card">
            <div class="mb-5 flex flex-wrap gap-3"><span class="status-badge status-info">{{ $letter->status->label() }}</span><span class="status-badge status-neutral">{{ ucfirst($letter->nature) }}</span></div>
            <dl class="grid gap-5 md:grid-cols-2">
                <div><dt class="text-xs text-slate-500">Nomor Agenda</dt><dd class="mt-1 font-semibold">{{ $letter->agenda_number }}</dd></div>
                <div><dt class="text-xs text-slate-500">Nomor Surat</dt><dd class="mt-1 font-semibold">{{ $letter->number ?: '-' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Tanggal Surat</dt><dd class="mt-1">{{ $letter->letter_date?->translatedFormat('d F Y') ?: '-' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Tanggal Diterima</dt><dd class="mt-1">{{ $letter->received_date?->translatedFormat('d F Y') }}</dd></div>
                <div><dt class="text-xs text-slate-500">Asal</dt><dd class="mt-1">{{ $letter->sender }}</dd></div>
                <div><dt class="text-xs text-slate-500">Tujuan</dt><dd class="mt-1">{{ $letter->destination ?: '-' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-xs text-slate-500">Perihal</dt><dd class="mt-1 font-semibold">{{ $letter->subject }}</dd></div>
            </dl>
        </section>

        <div class="space-y-6">
            <section class="portal-card">
                <h2 class="mb-4 font-semibold">Alur Tindak Lanjut</h2>
                <div class="grid gap-3">
                    @if($letter->status === \App\Enums\IncomingLetterStatus::Recorded) @can('incoming-letters.process')<button wire:click="dispose" class="spt-action spt-action-primary">Tandai Didisposisikan</button>@endcan
                    @elseif($letter->status === \App\Enums\IncomingLetterStatus::Disposed) @can('incoming-letters.process')<button wire:click="process" class="spt-action spt-action-primary">Mulai Proses</button>@endcan
                    @elseif($letter->status === \App\Enums\IncomingLetterStatus::Processing) @can('incoming-letters.process')<button wire:click="complete" class="spt-action spt-action-primary">Tandai Selesai</button>@endcan
                    @elseif($letter->status === \App\Enums\IncomingLetterStatus::Completed) @can('incoming-letters.archive')<button wire:click="archive" class="spt-action spt-action-primary">Arsipkan</button>@endcan
                    @else <p class="text-sm text-slate-500">Surat telah diarsipkan.</p> @endif
                </div>
            </section>

            <section class="portal-card">
                <h2 class="mb-4 font-semibold">File Surat Asli</h2>
                @if($letter->original_file_path)
                    <div class="document-card"><x-app.icon name="document" /><div class="min-w-0 flex-1"><strong class="block break-words text-sm">{{ $letter->original_file_name ?: 'File surat masuk' }}</strong></div><button wire:click="downloadOriginal" class="icon-button"><x-app.icon name="download" /></button></div>
                @else
                    <x-app.empty-state title="Tidak ada file lampiran" description="File surat masuk bersifat opsional." icon="archive" />
                @endif
            </section>
        </div>
    </div>
</div>
