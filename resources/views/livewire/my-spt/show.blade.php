<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('my-spt.index') }}" wire:navigate class="text-sm text-blue-700">
                ← SPT Saya
            </a>

            <h1 class="mt-2 text-2xl font-bold text-slate-900">
                {{ $letter->number ?: 'SPT #'.$letter->id }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                {{ $letter->subject ?: $letter->activityType?->name ?: '-' }}
            </p>
        </div>

        <x-app.status-badge :status="$letter->status" />
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <dl class="grid gap-5 md:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-400">Tanggal SPT</dt>
                <dd class="mt-1 font-medium">{{ $letter->letter_date?->translatedFormat('d F Y') ?: '-' }}</dd>
            </div>

            <div>
                <dt class="text-xs font-semibold uppercase text-slate-400">Jenis Kegiatan</dt>
                <dd class="mt-1 font-medium">{{ $letter->activityType?->name ?: 'Belum dikategorikan' }}</dd>
            </div>

            <div>
                <dt class="text-xs font-semibold uppercase text-slate-400">Periode</dt>
                <dd class="mt-1 font-medium">
                    {{ $letter->start_date?->translatedFormat('d F Y') ?: '-' }}
                    @if($letter->end_date && ! $letter->end_date->equalTo($letter->start_date))
                        – {{ $letter->end_date->translatedFormat('d F Y') }}
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-xs font-semibold uppercase text-slate-400">Lokasi</dt>
                <dd class="mt-1 font-medium">{{ $letter->location ?: '-' }}</dd>
            </div>

            <div class="md:col-span-2">
                <dt class="text-xs font-semibold uppercase text-slate-400">Dasar</dt>
                <dd class="mt-1 whitespace-pre-line text-sm text-slate-700">
                    {{ $letter->basis ?: '-' }}
                </dd>
            </div>

            <div class="md:col-span-2">
                <dt class="text-xs font-semibold uppercase text-slate-400">Keterangan</dt>
                <dd class="mt-1 whitespace-pre-line text-sm text-slate-700">
                    {{ $letter->description ?: '-' }}
                </dd>
            </div>
        </dl>
    </section>
</div>
