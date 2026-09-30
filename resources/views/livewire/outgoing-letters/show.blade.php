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

                @if($letter->sourceSpt)
                    <div class="md:col-span-2">
                        <dt class="text-xs text-slate-500">SPT Sumber</dt>
                        <dd class="mt-1">
                            <a
                                href="{{ route('letters.show', $letter->sourceSpt) }}"
                                wire:navigate
                                class="font-medium text-blue-700 hover:underline"
                            >
                                {{ $letter->sourceSpt->number ?: 'SPT #'.$letter->sourceSpt->id }}
                                Â· {{ $letter->sourceSpt->subject }}
                            </a>
                        </dd>
                    </div>
                @endif
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
                        <button
                            type="button"
                            wire:click="verify"
                            wire:loading.attr="disabled"
                            wire:target="verify"
                            wire:confirm="Verifikasi surat ini?"
                            class="spt-action spt-action-primary"
                        >
                            <span wire:loading.remove wire:target="verify">Verifikasi</span>
                            <span wire:loading wire:target="verify">Memproses...</span>
                        </button>
                    @endcan

                @elseif($letter->status === \App\Enums\OutgoingLetterStatus::Verified)
                    @can('outgoing-letters.approve')
                        <button
                            type="button"
                            wire:click="approve"
                            wire:loading.attr="disabled"
                            wire:target="approve"
                            wire:confirm="Setujui surat ini untuk proses penerbitan?"
                            class="spt-action spt-action-primary"
                        >
                            <span wire:loading.remove wire:target="approve">Setujui</span>
                            <span wire:loading wire:target="approve">Memproses...</span>
                        </button>
                    @endcan

                @elseif(in_array($letter->status, [\App\Enums\OutgoingLetterStatus::Approved, \App\Enums\OutgoingLetterStatus::Numbered], true))
                    @can('outgoing-letters.publish')
                        <button
                            type="button"
                            wire:click="publish"
                            wire:loading.attr="disabled"
                            wire:target="publish"
                            wire:confirm="Terbitkan surat ini? Nomor resmi akan dialokasikan dan surat masuk Register Surat Terbit."
                            class="spt-action spt-action-primary"
                        >
                            <span wire:loading.remove wire:target="publish">Terbitkan Surat</span>
                            <span wire:loading wire:target="publish">Menerbitkan...</span>
                        </button>
                    @endcan

                @elseif($letter->status === \App\Enums\OutgoingLetterStatus::Published)
                    @can('outgoing-letters.send')
                        <button
                            type="button"
                            wire:click="send"
                            wire:loading.attr="disabled"
                            wire:target="send"
                            wire:confirm="Tandai surat ini sebagai sudah dikirim?"
                            class="spt-action spt-action-primary"
                        >
                            <span wire:loading.remove wire:target="send">Tandai Dikirim</span>
                            <span wire:loading wire:target="send">Memproses...</span>
                        </button>
                    @endcan

                @elseif($letter->status === \App\Enums\OutgoingLetterStatus::Sent)
                    @can('outgoing-letters.archive')
                        <button
                            type="button"
                            wire:click="archive"
                            wire:loading.attr="disabled"
                            wire:target="archive"
                            wire:confirm="Arsipkan surat ini?"
                            class="spt-action spt-action-primary"
                        >
                            <span wire:loading.remove wire:target="archive">Arsipkan</span>
                            <span wire:loading wire:target="archive">Mengarsipkan...</span>
                        </button>
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
        'previewMode' => ! in_array(
            $letter->status,
            [
                \App\Enums\OutgoingLetterStatus::Published,
                \App\Enums\OutgoingLetterStatus::Sent,
                \App\Enums\OutgoingLetterStatus::Archived,
            ],
            true
        ),
    ])
</div>
