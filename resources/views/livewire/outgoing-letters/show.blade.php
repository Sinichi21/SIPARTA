<div class="space-y-6">
    <x-app.page-heading
        title="Detail Surat Keluar"
        :description="$letter->number ?: 'Draft #'.$letter->id"
    >
        <x-slot:actions>
            @if($letter->canBeEdited())
                @can('outgoing-letters.update')
                    <a href="{{ route('outgoing-letters.edit', $letter) }}" wire:navigate class="spt-action spt-action-view">
                        Edit Draft
                    </a>
                @endcan
            @endif

            <a href="{{ route('outgoing-letters.index') }}" wire:navigate class="spt-action spt-action-back">
                Kembali
            </a>
        </x-slot:actions>
    </x-app.page-heading>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @error('status')
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @error('manual_number')
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="portal-card">
            <div class="mb-5 flex flex-wrap items-center gap-3">
                <span class="status-badge status-info">{{ $letter->status->label() }}</span>
                <span class="status-badge status-neutral">{{ ucfirst($letter->nature) }}</span>

                @if(! $letter->number)
                    <span class="status-badge status-neutral">Belum bernomor</span>
                @endif
            </div>

            <dl class="grid gap-5 md:grid-cols-2">
                <div>
                    <dt class="text-xs text-slate-500">Jenis Surat</dt>
                    <dd class="mt-1 font-semibold">{{ $letter->letterType?->name ?: '-' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Template</dt>
                    <dd class="mt-1">{{ $letter->template?->name ?: '-' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Nomor Surat Final</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $letter->number ?: 'Dialokasikan saat terbit' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Tanggal Surat Final</dt>
                    <dd class="mt-1">
                        {{ $letter->letter_date?->translatedFormat('d F Y') ?: 'Ditetapkan saat terbit' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Mode Penomoran</dt>
                    <dd class="mt-1">
                        {{ $letter->numbering_mode === 'manual' ? 'Manual' : 'Otomatis saat terbit' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Mode Tanggal</dt>
                    <dd class="mt-1">
                        {{ $letter->date_mode === 'manual' ? 'Manual' : 'Tanggal penerbitan' }}
                    </dd>
                </div>

                @if($letter->numbering_mode === 'manual' && ! $letter->number)
                    <div>
                        <dt class="text-xs text-slate-500">Kandidat Nomor Manual</dt>
                        <dd class="mt-1 font-medium">{{ $letter->manual_number ?: '-' }}</dd>
                    </div>
                @endif

                @if($letter->date_mode === 'manual' && ! $letter->letter_date)
                    <div>
                        <dt class="text-xs text-slate-500">Kandidat Tanggal Manual</dt>
                        <dd class="mt-1">{{ $letter->manual_letter_date?->translatedFormat('d F Y') ?: '-' }}</dd>
                    </div>
                @endif

                <div>
                    <dt class="text-xs text-slate-500">Tujuan</dt>
                    <dd class="mt-1">{{ $letter->recipient }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Klasifikasi</dt>
                    <dd class="mt-1">{{ $letter->classification ?: '-' }}</dd>
                </div>

                <div class="md:col-span-2">
                    <dt class="text-xs text-slate-500">Perihal</dt>
                    <dd class="mt-1 font-semibold">{{ $letter->subject }}</dd>
                </div>
            </dl>

            @if(! $letter->number)
                <div class="mt-5 rounded-lg border border-blue-200 bg-blue-50 p-4 text-xs leading-5 text-blue-800">
                    Nomor resmi belum dialokasikan. Draft, verifikasi, dan persetujuan tidak mengubah sequence nomor surat.
                </div>
            @endif
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

                @elseif(in_array($letter->status, [\App\Enums\OutgoingLetterStatus::Approved, \App\Enums\OutgoingLetterStatus::Numbered], true))
                    @can('outgoing-letters.publish')
                        <button
                            wire:click="publish"
                            wire:confirm="Terbitkan surat ini? Nomor resmi akan dialokasikan dan surat masuk Register Surat Terbit."
                            class="spt-action spt-action-primary"
                        >
                            Terbitkan Surat
                        </button>
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

    @include('livewire.outgoing-letters.partials.a4-preview', [
        'letter' => $letter,
        'renderedBody' => $previewBody,
        'missingPlaceholders' => $missingPlaceholders,
        'previewMode' => $letter->status !== \App\Enums\OutgoingLetterStatus::Published,
    ])
</div>
