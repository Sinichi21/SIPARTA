<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Rekap Saya</h1>
            <p class="mt-1 text-sm text-slate-500">
                Ringkasan riwayat penugasan yang terkait langsung dengan personil akun Anda.
            </p>
        </div>

        <select wire:model.live="year" class="rounded-xl border-slate-200 text-sm">
            <option value="">Semua tahun</option>
            @foreach($years as $item)
                <option value="{{ $item }}">{{ $item }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach([
            ['Total SPT', $total],
            ['Diterbitkan', $published],
            ['SPT Terakhir', $latest?->letter_date?->translatedFormat('d M Y') ?: '-'],
        ] as [$label, $value])
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $label }}</p>
                <strong class="mt-2 block text-2xl text-slate-900">{{ $value }}</strong>
            </section>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-semibold text-slate-900">Riwayat Terbaru</h2>
        </header>

        <div class="divide-y divide-slate-100">
            @forelse($history as $letter)
                <a
                    href="{{ route('my-spt.show', $letter) }}"
                    wire:navigate
                    class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50"
                >
                    <div class="min-w-0">
                        <strong class="block truncate text-sm text-slate-900">
                            {{ $letter->subject ?: $letter->activityType?->name ?: '-' }}
                        </strong>
                        <span class="mt-1 block text-xs text-slate-500">
                            {{ $letter->number ?: 'SPT #'.$letter->id }}
                            · {{ $letter->location ?: '-' }}
                        </span>
                    </div>

                    <span class="shrink-0 text-xs text-slate-500">
                        {{ $letter->letter_date?->translatedFormat('d M Y') ?: '-' }}
                    </span>
                </a>
            @empty
                <p class="p-8 text-center text-sm text-slate-500">
                    Belum ada riwayat SPT.
                </p>
            @endforelse
        </div>
    </section>
</div>
