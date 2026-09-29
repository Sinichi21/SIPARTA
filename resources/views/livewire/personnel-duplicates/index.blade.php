<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('personnels.index') }}" wire:navigate class="inline-flex size-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50">←</a>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-950">Deteksi Duplikat Personil</h1>
                    <p class="mt-1 text-sm text-slate-500">Gabungkan record ganda tanpa menghilangkan riwayat SPT.</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Merge tidak dilakukan otomatis. Periksa NIP dan data personil sebelum menggabungkan.
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @error('merge')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
    @enderror
    @error('cleanup')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
    @enderror
    @error('manual')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
    @enderror


    <div class="rounded-2xl border border-blue-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-950">Merge Manual</h2>
                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700">Untuk data yang tidak terdeteksi otomatis</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Cari personil utama, lalu pilih satu atau beberapa record lain yang sebenarnya merupakan orang yang sama.</p>
                </div>
                <div class="text-xs text-slate-500">Merge tetap diblokir jika NIP berbeda.</div>
            </div>
        </div>

        <div class="grid gap-6 p-5 xl:grid-cols-2">
            <div>
                <label class="text-sm font-bold text-slate-800">1. Pilih Personil Utama</label>
                <p class="mt-1 text-xs text-slate-500">Record ini akan dipertahankan setelah merge.</p>

                <input
                    type="search"
                    wire:model.live.debounce.300ms="manualPrimarySearch"
                    placeholder="Cari nama atau NIP personil utama..."
                    class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                >

                @if (mb_strlen(trim($manualPrimarySearch)) >= 2)
                    <div class="mt-3 max-h-72 overflow-y-auto rounded-xl border border-slate-200">
                        @forelse ($manualPrimaryResults as $personnel)
                            <label class="flex cursor-pointer items-start gap-3 border-b border-slate-100 p-3 last:border-b-0 hover:bg-blue-50/60">
                                <input
                                    type="radio"
                                    wire:model.live="manualPrimaryId"
                                    value="{{ $personnel->id }}"
                                    class="mt-1 size-4 border-slate-300 text-blue-600 focus:ring-blue-500"
                                >
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-slate-900">{{ $personnel->name }}</span>
                                        <span class="font-mono text-[11px] text-slate-400">#{{ $personnel->id }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        NIP: {{ $personnel->nip ?: '-' }} · {{ $personnel->unit?->name ?: 'Tanpa unit' }} · {{ $personnel->letters_count }} SPT
                                    </div>
                                </div>
                            </label>
                        @empty
                            <div class="p-4 text-center text-sm text-slate-500">Personil tidak ditemukan.</div>
                        @endforelse
                    </div>
                @endif

                @if ($manualPrimaryId)
                    <div class="mt-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Record utama dipilih: <strong>#{{ $manualPrimaryId }}</strong>
                    </div>
                @endif
            </div>

            <div>
                <label class="text-sm font-bold text-slate-800">2. Pilih Record Duplikat</label>
                <p class="mt-1 text-xs text-slate-500">Boleh memilih lebih dari satu record untuk digabungkan ke personil utama.</p>

                <input
                    type="search"
                    wire:model.live.debounce.300ms="manualDuplicateSearch"
                    placeholder="Cari nama atau NIP record duplikat..."
                    class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                >

                @if (mb_strlen(trim($manualDuplicateSearch)) >= 2)
                    <div class="mt-3 max-h-72 overflow-y-auto rounded-xl border border-slate-200">
                        @forelse ($manualDuplicateResults as $personnel)
                            <label class="flex cursor-pointer items-start gap-3 border-b border-slate-100 p-3 last:border-b-0 hover:bg-amber-50/60">
                                <input
                                    type="checkbox"
                                    wire:model.live="manualDuplicateIds"
                                    value="{{ $personnel->id }}"
                                    class="mt-1 size-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                >
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-slate-900">{{ $personnel->name }}</span>
                                        <span class="font-mono text-[11px] text-slate-400">#{{ $personnel->id }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        NIP: {{ $personnel->nip ?: '-' }} · {{ $personnel->unit?->name ?: 'Tanpa unit' }} · {{ $personnel->letters_count }} SPT
                                    </div>
                                </div>
                            </label>
                        @empty
                            <div class="p-4 text-center text-sm text-slate-500">Record duplikat tidak ditemukan.</div>
                        @endforelse
                    </div>
                @endif

                @if (count($manualDuplicateIds) > 0)
                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ count($manualDuplicateIds) }} record dipilih untuk digabungkan.
                    </div>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-slate-500">Riwayat SPT akan dipindahkan ke record utama dan record duplikat di-soft-delete.</p>
            <button
                type="button"
                wire:click="mergeManual"
                wire:confirm="Yakin melakukan merge manual? Pastikan personil utama dan seluruh record yang dipilih benar-benar orang yang sama."
                @disabled(! $manualPrimaryId || count($manualDuplicateIds) < 1)
                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
            >
                Merge Manual {{ count($manualDuplicateIds) > 0 ? '(' . count($manualDuplicateIds) . ')' : '' }}
            </button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-950">Kandidat Duplikat</h2>
                <p class="mt-1 text-sm text-slate-500">Dikelompokkan berdasarkan nama inti setelah normalisasi huruf besar/kecil, tanda baca, nomor daftar, dan gelar umum seperti S.Kom., S.T., M.T., dan sejenisnya.</p>
            </div>
            <div class="rounded-xl bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                {{ $duplicateGroups->count() }} kelompok ditemukan
            </div>
        </div>

        <div class="mt-5">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama atau NIP..."
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
            >
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($duplicateGroups as $groupKey => $group)
            @php
                $distinctNips = $group->pluck('nip')->filter()->map(fn ($nip) => preg_replace('/\D+/', '', $nip))->filter()->unique();
                $hasNipConflict = $distinctNips->count() > 1;
            @endphp

            <div class="overflow-hidden rounded-2xl border {{ $hasNipConflict ? 'border-red-200' : 'border-slate-200' }} bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-bold text-slate-950">{{ $group->first()->name }}</h3>
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">{{ $group->count() }} record</span>
                            @if ($hasNipConflict)
                                <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700">NIP berbeda — jangan merge sebelum diverifikasi</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Pilih record yang akan dipertahankan sebagai data utama.</p>
                    </div>

                    <button
                        type="button"
                        wire:click="mergeGroup(@js($groupKey))"
                        wire:confirm="Gabungkan seluruh record pada kelompok ini ke record utama? Riwayat SPT akan dipindahkan dan record duplikat akan di-soft-delete."
                        @disabled($hasNipConflict)
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                    >
                        Gabungkan {{ max(0, $group->count() - 1) }} Duplikat
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="w-20 px-5 py-3 text-left">Utama</th>
                                <th class="px-4 py-3 text-left">ID</th>
                                <th class="px-4 py-3 text-left">Nama</th>
                                <th class="px-4 py-3 text-left">NIP</th>
                                <th class="px-4 py-3 text-left">Unit</th>
                                <th class="px-4 py-3 text-left">Jabatan</th>
                                <th class="px-4 py-3 text-center">SPT</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($group as $personnel)
                                <tr class="{{ (int) ($primarySelections[$groupKey] ?? 0) === $personnel->id ? 'bg-blue-50/60' : '' }}">
                                    <td class="px-5 py-4">
                                        <input
                                            type="radio"
                                            wire:model.live="primarySelections.{{ $groupKey }}"
                                            value="{{ $personnel->id }}"
                                            class="size-4 border-slate-300 text-blue-600 focus:ring-blue-500"
                                        >
                                    </td>
                                    <td class="px-4 py-4 font-mono text-xs text-slate-500">#{{ $personnel->id }}</td>
                                    <td class="px-4 py-4 font-semibold text-slate-900">{{ $personnel->name }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $personnel->nip ?: '-' }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $personnel->unit?->name ?: '-' }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $personnel->position ?: '-' }}</td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700">{{ $personnel->letters_count }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-10 text-center">
                <div class="text-3xl">✓</div>
                <h3 class="mt-3 text-lg font-bold text-emerald-900">Tidak ada nama duplikat yang terdeteksi</h3>
                <p class="mt-1 text-sm text-emerald-700">Data personil dengan nama normalisasi yang sama sudah bersih.</p>
            </div>
        @endforelse
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-bold text-slate-950">Record Mencurigakan Hasil Import Lama</h2>
                <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700">{{ $suspiciousPersonnel->count() }} data</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">Mendeteksi record yang hanya berisi fragmen gelar seperti S.T., M.T., S.Kom., dan sejenisnya akibat parser import lama.</p>
        </div>

        @if ($suspiciousPersonnel->isEmpty())
            <div class="p-8 text-center text-sm text-slate-500">Tidak ada record mencurigakan.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Nama Record</th>
                            <th class="px-4 py-3 text-left">NIP</th>
                            <th class="px-4 py-3 text-center">Terhubung ke SPT</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($suspiciousPersonnel as $personnel)
                            <tr>
                                <td class="px-5 py-4 font-bold text-red-700">{{ $personnel->name }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $personnel->nip ?: '-' }}</td>
                                <td class="px-4 py-4 text-center">{{ $personnel->letters_count }}</td>
                                <td class="px-5 py-4 text-right">
                                    <button
                                        type="button"
                                        wire:click="removeSuspicious({{ $personnel->id }})"
                                        wire:confirm="Record '{{ addslashes($personnel->name) }}' akan dilepas dari seluruh relasi SPT dan di-soft-delete. Lanjutkan?"
                                        class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100"
                                    >
                                        Bersihkan Record
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
