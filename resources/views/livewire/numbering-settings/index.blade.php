<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Pengaturan Nomor Surat</h1>
        <p class="mt-1 text-sm text-slate-500">Atur nomor terakhir dan placeholder custom yang dapat dipakai pada format nomor surat.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-blue-900">Sequence Surat Keluar</h2>
        <p class="mt-1 text-sm text-slate-500">Isi nomor terakhir yang sudah dipakai sebelum SIPARTA mulai mengalokasikan nomor otomatis.</p>

        <form wire:submit="saveSequence" class="mt-5 grid gap-4 md:grid-cols-3">
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Tahun</span>
                <input type="number" wire:model.live="sequenceYear" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Nomor terakhir terpakai</span>
                <input type="number" min="0" wire:model="lastNumber" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                @error('lastNumber') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <div class="flex items-end">
                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Simpan Sequence</button>
            </div>
        </form>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-1">
            <h2 class="text-lg font-semibold text-blue-900">Custom Placeholder</h2>
            <p class="text-sm text-slate-500">Gunakan key seperti <code>{tipe_surat}</code> pada Format Nomor di Jenis Surat.</p>
        </div>

        <form wire:submit="savePlaceholder" class="mt-5 grid gap-4 md:grid-cols-2">
            <label>
                <span class="mb-1 block text-sm font-medium">Key</span>
                <input wire:model="key" placeholder="tipe_surat" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                @error('key') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label>
                <span class="mb-1 block text-sm font-medium">Label field</span>
                <input wire:model="label" placeholder="Tipe Surat" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                @error('label') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label>
                <span class="mb-1 block text-sm font-medium">Tipe</span>
                <select wire:model.live="type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="select">Select</option>
                    <option value="text">Text</option>
                </select>
            </label>
            @if($type === 'select')
                <label>
                    <span class="mb-1 block text-sm font-medium">Pilihan</span>
                    <textarea wire:model="optionsText" rows="4" placeholder="A&#10;B&#10;C" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    <span class="mt-1 block text-xs text-slate-500">Satu pilihan per baris.</span>
                    @error('optionsText') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            @endif
            <div class="flex flex-wrap items-center gap-4 md:col-span-2">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_required"> Wajib diisi</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active"> Aktif</label>
            </div>
            <div class="flex gap-2 md:col-span-2">
                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">{{ $editingId ? 'Simpan Perubahan' : 'Tambah Placeholder' }}</button>
                @if($editingId)
                    <button type="button" wire:click="resetPlaceholderForm" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Batal</button>
                @endif
            </div>
        </form>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 text-left text-slate-500">
                    <tr><th class="py-2">Placeholder</th><th>Label</th><th>Tipe</th><th>Pilihan</th><th>Status</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($placeholders as $placeholder)
                        <tr>
                            <td class="py-3 font-mono">{<span></span>{{ $placeholder->key }}<span></span>}</td>
                            <td>{{ $placeholder->label }}</td>
                            <td>{{ ucfirst($placeholder->type) }}</td>
                            <td>{{ $placeholder->type === 'select' ? implode(', ', $placeholder->normalizedOptions()) : '-' }}</td>
                            <td>{{ $placeholder->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="text-right"><button wire:click="editPlaceholder({{ $placeholder->id }})" class="text-blue-700 hover:underline">Edit</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-slate-500">Belum ada custom placeholder.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
