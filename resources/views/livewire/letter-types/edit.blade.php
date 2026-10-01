<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl">
            Edit Jenis Surat
        </flux:heading>

        <flux:text class="mt-1">
            Ubah identitas dan pola nomor surat.
        </flux:text>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-5">
            <flux:input
                wire:model="code"
                label="Kode"
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

            <div class="rounded-lg bg-blue-50 p-4 text-sm text-slate-700">
                <p class="font-medium">Token tersedia</p>
                <div class="mt-2 font-mono text-xs">
                    {sequence} {sequence_padded} {type} {year} {year_short} {month} {month_roman}
                    @foreach($customPlaceholders as $placeholder)
                        {<span></span>{{ $placeholder->key }}<span></span>}
                    @endforeach
                </div>
            
                <div class="mt-3 text-xs text-slate-600">Custom placeholder dikelola di menu <strong>Pengaturan Nomor</strong>. Jika placeholder bertipe select dipakai pada pola ini, field select otomatis muncul di form Surat Keluar.</div>
            </div>

            <flux:checkbox
                wire:model="requires_personnel"
                label="Jenis surat membutuhkan daftar personil"
            />

            <flux:checkbox
                wire:model="is_active"
                label="Jenis surat aktif"
            />

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                <flux:button
                    href="{{ route('letter-types.index') }}"
                    wire:navigate
                    variant="ghost"
                >
                    Batal
                </flux:button>

                <flux:button type="submit" variant="primary">
                    Simpan Perubahan
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
