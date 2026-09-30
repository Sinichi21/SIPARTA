<div class="space-y-6">
    <x-app.page-heading
        title="Detail Surat Keluar"
        :description="$letter->number ?: 'Draft #'.$letter->id"
    >
        <x-slot:actions>
            @if($letter->canBeEdited())
                @can('outgoing-letters.update')
                    <a href="{{ route('outgoing-letters.edit', $letter) }}" wire:navigate class="spt-action spt-action-view">Edit Draft</a>
                @endcan
            @endif
            <a href="{{ route('outgoing-letters.index') }}" wire:navigate class="spt-action spt-action-back">Kembali</a>
        </x-slot:actions>
    </x-app.page-heading>

    @if(session('success'))<div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @error('status')<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>@enderror
    @error('number')<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>@enderror

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="portal-card">
            <div class="mb-5 flex flex-wrap items-center gap-3">
                <span class="status-badge status-info">{{ $letter->status->label() }}</span>
                <span class="status-badge status-neutral">{{ ucfirst($letter->nature) }}</span>
            </div>

            <dl class="grid gap-5 md:grid-cols-2">
                <div><dt class="text-xs text-slate-500">Jenis Surat</dt><dd class="mt-1 font-semibold">{{ $letter->letterType?->name ?: '-' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Template</dt><dd class="mt-1">{{ $letter->template?->name ?: '-' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Nomor Surat</dt><dd class="mt-1 font-semibold">{{ $letter->number ?: 'Belum bernomor' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Tanggal Surat</dt><dd class="mt-1">{{ $letter->letter_date?->translatedFormat('d F Y') ?: '-' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Tujuan</dt><dd class="mt-1">{{ $letter->recipient }}</dd></div>
                <div><dt class="text-xs text-slate-500">Klasifikasi</dt><dd class="mt-1">{{ $letter->classification ?: '-' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-xs text-slate-500">Perihal</dt><dd class="mt-1 font-semibold">{{ $letter->subject }}</dd></div>
                <div class="md:col-span-2"><dt class="text-xs text-slate-500">Isi Surat</dt><dd class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $letter->content_html ? strip_tags($letter->content_html) : 'Belum ada isi surat.' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-xs text-slate-500">Catatan Internal</dt><dd class="mt-1 whitespace-pre-line">{{ $letter->notes ?: '-' }}</dd></div>
            </dl>
        </section>

        <section class="portal-card">
            <h2 class="mb-4 font-semibold">Workflow Surat Keluar</h2>

            <div class="grid gap-3">
                @if($letter->status === \App\Enums\OutgoingLetterStatus::Draft)
                    @can('outgoing-letters.verify')
                        <button wire:click="verify" class="spt-action spt-action-primary">Verifikasi</button>
                    @endcan

                @elseif($letter->status === \App\Enums\OutgoingLetterStatus::Verified)
                    @can('outgoing-letters.approve')
                        <button wire:click="approve" class="spt-action spt-action-primary">Setujui</button>
                    @endcan

                @elseif($letter->status === \App\Enums\OutgoingLetterStatus::Approved)
                    @can('outgoing-letters.number')
                        <label>Nomor Surat<input wire:model="number" type="text"></label>
                        <label>Tanggal Surat<input wire:model="letter_date" type="date"></label>
                        <button wire:click="assignNumber" class="spt-action spt-action-primary">Tetapkan Nomor</button>
                    @endcan

                @elseif($letter->status === \App\Enums\OutgoingLetterStatus::Numbered)
                    @can('outgoing-letters.publish')
                        <button wire:click="publish" wire:confirm="Terbitkan surat ini dan masukkan ke Register Surat Terbit?" class="spt-action spt-action-primary">Terbitkan Surat</button>
                    @endcan

                @elseif($letter->status === \App\Enums\OutgoingLetterStatus::Published)
                    @can('outgoing-letters.send')
                        <button wire:click="send" class="spt-action spt-action-primary">Tandai Dikirim</button>
                    @endcan

                @elseif($letter->status === \App\Enums\OutgoingLetterStatus::Sent)
                    @can('outgoing-letters.archive')
                        <button wire:click="archive" class="spt-action spt-action-primary">Arsipkan</button>
                    @endcan

                @else
                    <p class="text-sm text-slate-500">Workflow surat telah selesai.</p>
                @endif
            </div>

            <div class="mt-6 border-t border-slate-100 pt-5 text-xs text-slate-500">
                <p>Dibuat: {{ $letter->creator?->name ?: '-' }}</p>
                @if($letter->verifier)<p class="mt-2">Verifikator: {{ $letter->verifier->name }}</p>@endif
                @if($letter->approver)<p class="mt-2">Penyetuju: {{ $letter->approver->name }}</p>@endif
                @if($letter->issuer)<p class="mt-2">Penerbit: {{ $letter->issuer->name }}</p>@endif
            </div>
        </section>
    </div>
</div>
