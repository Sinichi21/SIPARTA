<div>
    {{-- He who is contented is rich. - Laozi --}}
</div>
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Edit Personil</h1>
        <p class="mt-1 text-sm text-slate-500">
            Perbarui data {{ $personnel->name }}.
        </p>
    </div>

    <form
        wire:submit="save"
        class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900"
    >
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Nama *</label>
                <input wire:model="name" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">NIP</label>
                <input wire:model="nip" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                @error('nip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Unit / Tim</label>
                <select wire:model="unit_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Pilih unit</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Jabatan</label>
                <input wire:model="position" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Pangkat</label>
                <input wire:model="rank" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Golongan</label>
                <input wire:model="grade" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Email</label>
                <input type="email" wire:model="email" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Nomor Telepon</label>
                <input wire:model="phone" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
            <a
                href="{{ route('personnels.index') }}"
                wire:navigate
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium"
            >
                Batal
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="rounded-lg bg-blue-700 px-5 py-2 text-sm font-semibold text-white"
            >
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>