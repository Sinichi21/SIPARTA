<div class="mx-auto max-w-3xl space-y-6">

    <div>
        <flux:heading size="xl">
            Tambah Jenis Surat
        </flux:heading>

        <flux:text class="mt-1">
            Tentukan jenis serta pola nomor surat.
        </flux:text>
    </div>

    <flux:card>
        <form
            wire:submit="save"
            class="space-y-5"
        >
            <flux:input
                wire:model="code"
                label="Kode"
                placeholder="SPT"
                required
            />

            <flux:input
                wire:model="name"
                label="Nama"
                required
            />

            <flux:textarea
                wire:model="description"
                label="Deskripsi"
            />

            <flux:input
                wire:model="numbering_pattern"
                label="Format Nomor"
            />

            <div class="rounded-lg bg-blue-50 p-4 text-sm">
                Token tersedia:

                <div class="mt-2 font-mono text-xs">
                    {sequence}
                    {type}
                    {year}
                    {month}
                    {month_roman}
                    {unit}
                </div>

                <div class="mt-3">
                    Contoh:
                    <strong>
                        001/SPT/IX/2026
                    </strong>
                </div>
            </div>

            <flux:checkbox
                wire:model="requires_personnel"
                label="Jenis surat membutuhkan daftar personil"
            />

            <div class="flex justify-end gap-3">
                <flux:button
                    href="{{ route('letter-types.index') }}"
                    wire:navigate
                    variant="ghost"
                >
                    Batal
                </flux:button>

                <flux:button
                    type="submit"
                    variant="primary"
                >
                    Simpan
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>