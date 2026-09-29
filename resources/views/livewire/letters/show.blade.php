<div class="mx-auto max-w-5xl space-y-6">
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @error('status')
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ $message }}
        </div>
    @enderror

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-medium text-blue-700">
                Surat Perintah Tugas
            </p>

            <h1 class="mt-1 text-2xl font-bold">
                {{ $letter->number ?: 'Draft SPT #' . $letter->id }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                {{ $letter->subject }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($letter->status === LetterStatus::Draft)
                @can('letters.update')
                    <a
                        href="{{ route('letters.edit', $letter) }}"
                        wire:navigate
                        class="rounded-lg border border-blue-300 px-4 py-2 text-sm font-medium text-blue-700"
                    >
                        Edit
                    </a>
                @endcan

                @can('letters.publish')
                    <button
                        type="button"
                        wire:click="publish"
                        wire:confirm="Terbitkan SPT ini? Setelah diterbitkan data tidak dapat diedit langsung."
                        class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white"
                    >
                        Terbitkan
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <dl class="grid gap-5 md:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Status</dt>
                <dd class="mt-1 font-medium">{{ $letter->status->label() }}</dd>
            </div>

            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Tanggal Surat</dt>
                <dd class="mt-1">{{ $letter->letter_date?->format('d/m/Y') }}</dd>
            </div>

            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Jenis Kegiatan</dt>
                <dd class="mt-1">{{ $letter->activityType?->name ?: '-' }}</dd>
            </div>

            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Lokasi</dt>
                <dd class="mt-1">{{ $letter->location }}</dd>
            </div>

            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Periode</dt>
                <dd class="mt-1">
                    {{ $letter->start_date?->format('d/m/Y') }}
                    –
                    {{ $letter->end_date?->format('d/m/Y') }}
                </dd>
            </div>

            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Dibuat oleh</dt>
                <dd class="mt-1">{{ $letter->creator?->name ?: '-' }}</dd>
            </div>
        </dl>

        <div class="mt-6">
            <h2 class="text-sm font-semibold uppercase text-slate-500">
                Dasar
            </h2>
            <p class="mt-2 whitespace-pre-line text-sm">
                {{ $letter->basis ?: '-' }}
            </p>
        </div>

        <div class="mt-6">
            <h2 class="text-sm font-semibold uppercase text-slate-500">
                Personil
            </h2>

            <ol class="mt-3 list-decimal space-y-2 pl-5">
                @foreach ($letter->personnels as $personnel)
                    <li class="text-sm">
                        <span class="font-medium">{{ $personnel->name }}</span>
                        @if ($personnel->nip)
                            <span class="text-slate-500"> — {{ $personnel->nip }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>

        @if ($letter->description)
            <div class="mt-6">
                <h2 class="text-sm font-semibold uppercase text-slate-500">
                    Keterangan
                </h2>
                <p class="mt-2 whitespace-pre-line text-sm">
                    {{ $letter->description }}
                </p>
            </div>
        @endif
    </section>

    @if (
        $letter->status !== LetterStatus::Cancelled
        && auth()->user()->can('letters.cancel')
    )
        <section class="rounded-xl border border-red-200 bg-red-50 p-6">
            <h2 class="font-semibold text-red-900">
                Batalkan SPT
            </h2>

            <textarea
                wire:model="cancellationReason"
                rows="3"
                placeholder="Alasan pembatalan..."
                class="mt-3 w-full rounded-lg border border-red-200 bg-white px-3 py-2"
            ></textarea>

            @error('cancellationReason')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror

            <button
                type="button"
                wire:click="cancel"
                wire:confirm="Yakin membatalkan SPT ini?"
                class="mt-3 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white"
            >
                Batalkan SPT
            </button>
        </section>
    @endif
</div>