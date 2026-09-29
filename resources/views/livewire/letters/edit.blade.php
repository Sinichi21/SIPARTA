<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Edit SPT</h1>
        <p class="mt-1 text-sm text-slate-500">
            Hanya draft yang dapat diedit.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Nomor SPT</label>
                    <input wire:model="number" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Jenis Kegiatan *</label>
                    <select wire:model="activity_type_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        @foreach ($activityTypes as $activityType)
                            <option value="{{ $activityType->id }}">
                                {{ $activityType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Perihal *</label>
                    <input wire:model="subject" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Surat *</label>
                    <input type="date" wire:model="letter_date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Lokasi *</label>
                    <input wire:model="location" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Mulai *</label>
                    <input type="date" wire:model="start_date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Selesai *</label>
                    <input type="date" wire:model="end_date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Dasar</label>
                    <textarea wire:model="basis" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Jenis Record *</label>
                    <select wire:model="record_type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="normal">SPT Normal</option>
                        <option value="attendance_correction">Koreksi Absensi</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Gunakan Koreksi Absensi hanya untuk administrasi lupa absen, bukan penugasan lapangan baru.</p>
                    @error('record_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Keterangan</label>
                    <textarea wire:model="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-blue-900">
                Personil
            </h2>

            <input
                type="search"
                wire:model.live.debounce.300ms="personnelSearch"
                placeholder="Cari personil..."
                class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2"
            >

            <div class="mt-4 max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200">
                @foreach ($personnels as $personnel)
                    <label class="flex items-center gap-3 p-3">
                        <input
                            type="checkbox"
                            wire:model="personnel_ids"
                            value="{{ $personnel->id }}"
                        >

                        <span class="text-sm">
                            {{ $personnel->name }}
                        </span>
                    </label>
                @endforeach
            </div>

            @error('personnel_ids')
                <p class="mt-2 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </section>

        <div class="flex justify-end gap-3">
            <a
                href="{{ route('letters.show', $letter) }}"
                wire:navigate
                class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium"
            >
                Batal
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white"
            >
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>