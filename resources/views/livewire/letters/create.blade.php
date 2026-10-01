<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Tambah SPT</h1>
        <p class="mt-1 text-sm text-slate-500">
            Buat Surat Perintah Tugas baru.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-lg font-semibold text-blue-900">
                Informasi SPT
            </h2>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Nomor SPT</label>
                    <input wire:model="number" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Jenis Kegiatan *</label>
                    <select wire:model="activity_type_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Pilih kegiatan</option>
                        @foreach ($activityTypes as $activityType)
                            <option value="{{ $activityType->id }}">
                                {{ $activityType->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('activity_type_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Perihal *</label>
                    <input wire:model="subject" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Surat *</label>
                    <input type="date" wire:model="letter_date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Lokasi *</label>
                    <input wire:model="location" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('location') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Mulai *</label>
                    <input type="date" wire:model="start_date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Selesai *</label>
                    <input type="date" wire:model="end_date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Dasar Surat</label>
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
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-blue-900">
                        Personil
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        @if($personnel_scope === 'team')
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-blue-900">Pilih Tim *</span>
                        <select wire:model.live="personnel_team_id" class="w-full rounded-lg border border-blue-200 bg-white px-3 py-2">
                            <option value="">Pilih tim</option>
                            @foreach($personnelTeams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }} — {{ $team->personnels_count }} anggota aktif</option>
                            @endforeach
                        </select>
                        @error('personnel_team_id') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <p class="mt-2 text-xs text-blue-800">Anggota aktif tim akan disalin ke SPT saat disimpan agar rekap per personil tetap akurat.</p>
                </div>
            @endif

            @if($personnel_scope === 'all')
                            SPT berlaku untuk seluruh pegawai. Tidak dibuat relasi personil individual.
                        @else
                            {{ count($personnel_ids) }} dari {{ $totalActivePersonnel }} personil aktif dipilih.
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="selectAllPersonnel"
                        wire:loading.attr="disabled"
                        wire:target="selectAllPersonnel"
                        @disabled($personnel_scope !== 'selected')
                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="selectAllPersonnel">
                            Pilih Semua Personil Aktif
                        </span>
                        <span wire:loading wire:target="selectAllPersonnel">
                            Memilih...
                        </span>
                    </button>

                    @if (filled($personnelSearch))
                        <button
                            type="button"
                            wire:click="selectVisiblePersonnel"
                            wire:loading.attr="disabled"
                            wire:target="selectVisiblePersonnel"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
                        >
                            Pilih Hasil Pencarian
                        </button>
                    @endif

                    @if (count($personnel_ids) > 0)
                        <button
                            type="button"
                            wire:click="clearAllPersonnel"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                        >
                            Batalkan Semua
                        </button>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="useSelectedPersonnelScope"
                    class="rounded-lg border px-3 py-2 text-xs font-semibold transition {{ $personnel_scope === 'selected' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    Personil Tertentu
                </button>

                <button
                    type="button"
                    wire:click="useTeamPersonnelScope"
                    class="rounded-lg border px-3 py-2 text-xs font-semibold transition {{ $personnel_scope === 'team' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    Tim
                </button>

                <button
                    type="button"
                    wire:click="useAllPersonnelScope"
                    class="rounded-lg border px-3 py-2 text-xs font-semibold transition {{ $personnel_scope === 'all' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    Seluruh Pegawai
                </button>
            </div>

            @if($personnel_scope === 'all')
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                    SPT akan dicatat dengan cakupan <strong>Seluruh Pegawai</strong>.
                    Nama personil tidak ditempel satu per satu sehingga rekap personil individual tetap akurat.
                    Daftar personil di bawah tetap ditampilkan sebagai referensi.
                </div>
            @endif

            @error('personnel_scope')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror

            @if ($totalActivePersonnel > 100 && blank($personnelSearch))
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                    Daftar di bawah menampilkan maksimal 100 personil agar halaman tetap ringan.
                    Tombol <strong>Pilih Semua Personil Aktif</strong> tetap memilih seluruh {{ $totalActivePersonnel }} personil aktif.
                </div>
            @endif

            <input
                type="search"
                wire:model.live.debounce.300ms="personnelSearch"
                @disabled($personnel_scope === 'all')
                placeholder="Cari personil..."
                class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2"
            >

            <div class="mt-4 max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200">
                @forelse ($personnels as $personnel)
                    <label class="flex cursor-pointer items-center gap-3 p-3 hover:bg-blue-50">
                        <input
                            type="checkbox"
                            wire:model="personnel_ids"
                            @disabled($personnel_scope === 'all')
                            value="{{ $personnel->id }}"
                            class="rounded border-slate-300 text-blue-700"
                        >

                        <span>
                            <span class="block text-sm font-medium">
                                {{ $personnel->name }}
                            </span>

                            <span class="text-xs text-slate-500">
                                {{ $personnel->nip ?: 'Tanpa NIP' }}
                                @if ($personnel->position)
                                    · {{ $personnel->position }}
                                @endif
                            </span>
                        </span>
                    </label>
                @empty
                    <div class="p-6 text-center text-sm text-slate-500">
                        Personil tidak ditemukan.
                    </div>
                @endforelse
            </div>

            @error('personnel_ids')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </section>

        <div class="flex justify-end gap-3">
            <a
                href="{{ route('letters.index') }}"
                wire:navigate
                class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium"
            >
                Batal
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save">
                    Simpan Draft
                </span>
                <span wire:loading wire:target="save">
                    Menyimpan...
                </span>
            </button>
        </div>
    </form>
</div>
