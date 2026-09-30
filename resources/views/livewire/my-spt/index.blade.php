<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">SPT Saya</h1>
        <p class="mt-1 text-sm text-slate-500">
            Hanya menampilkan SPT yang secara eksplisit menugaskan personil yang terhubung ke akun Anda.
        </p>
    </div>

    @if(! auth()->user()->personnel_id)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Akun belum ditautkan ke data personil.
        </div>
    @endif

    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_180px]">
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Cari nomor, kegiatan, atau lokasi..."
            class="rounded-xl border-slate-200 text-sm"
        >

        <select wire:model.live="year" class="rounded-xl border-slate-200 text-sm">
            <option value="">Semua tahun</option>
            @foreach($years as $item)
                <option value="{{ $item }}">{{ $item }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Nomor</th>
                        <th class="px-5 py-3">Kegiatan</th>
                        <th class="px-5 py-3">Periode</th>
                        <th class="px-5 py-3">Lokasi</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($letters as $letter)
                        <tr>
                            <td class="px-5 py-4">
                                <a
                                    href="{{ route('my-spt.show', $letter) }}"
                                    wire:navigate
                                    class="font-semibold text-blue-700"
                                >
                                    {{ $letter->number ?: 'SPT #'.$letter->id }}
                                </a>
                                <span class="mt-1 block text-xs text-slate-400">
                                    {{ $letter->letter_date?->translatedFormat('d M Y') ?: '-' }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                {{ $letter->subject ?: $letter->activityType?->name ?: '-' }}
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                {{ $letter->start_date?->translatedFormat('d M Y') ?: '-' }}
                                @if($letter->end_date && ! $letter->end_date->equalTo($letter->start_date))
                                    – {{ $letter->end_date->translatedFormat('d M Y') }}
                                @endif
                            </td>

                            <td class="px-5 py-4">{{ $letter->location ?: '-' }}</td>

                            <td class="px-5 py-4">
                                <x-app.status-badge :status="$letter->status" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">
                                Belum ada SPT personal yang terkait dengan akun Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $letters->links() }}
</div>
