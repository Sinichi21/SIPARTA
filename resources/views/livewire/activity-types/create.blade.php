<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold">
            Tambah Jenis Kegiatan
        </h1>
    </div>

    <form
        wire:submit="save"
        class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
    >
        <div>
            <label class="mb-1 block text-sm font-medium">
                Kode
            </label>

            <input
                wire:model="code"
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
            >
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">
                Nama *
            </label>

            <input
                wire:model="name"
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
            >
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">
                Deskripsi
            </label>

            <textarea
                wire:model="description"
                rows="4"
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
            ></textarea>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
            <a
                href="{{ route('activity-types.index') }}"
                wire:navigate
                class="rounded-lg border border-slate-300 px-4 py-2"
            >
                Batal
            </a>

            <button
                type="submit"
                class="rounded-lg bg-blue-700 px-5 py-2 font-semibold text-white"
            >
                Simpan
            </button>
        </div>
    </form>
</div>