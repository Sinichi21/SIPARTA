<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="grid gap-5 md:grid-cols-2">
        <label class="grid gap-1.5 text-sm font-medium">
            <span>Nama Template *</span>
            <input wire:model="name" class="rounded-xl border-slate-200">
            @error('name')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="grid gap-1.5 text-sm font-medium">
            <span>Kode Template *</span>
            <input wire:model="code" class="rounded-xl border-slate-200 font-mono">
            @error('code')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="grid gap-1.5 text-sm font-medium">
            <span>Jenis Surat *</span>
            <select wire:model="letter_type_id" class="rounded-xl border-slate-200">
                <option value="">Pilih jenis surat</option>
                @foreach($letterTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
            @error('letter_type_id')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>

        <div class="flex flex-wrap items-end gap-5 pb-2">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="is_default" class="rounded border-slate-300">
                Template default
            </label>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="is_active" class="rounded border-slate-300">
                Aktif
            </label>
        </div>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="font-bold text-slate-900">Isi Template</h2>
            <p class="mt-1 text-xs text-slate-500">
                Editor CKEditor 5. Placeholder dipertahankan dalam format <code>@{{nama_placeholder}}</code>.
            </p>
        </div>

        <details class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs">
            <summary class="cursor-pointer font-semibold text-slate-700">Daftar Placeholder</summary>
            <div class="mt-3 grid gap-1 font-mono text-slate-600 sm:grid-cols-2">
                <span>@{{nomor_surat}}</span>
                <span>@{{tanggal_surat}}</span>
                <span>@{{kegiatan}}</span>
                <span>@{{jenis_kegiatan}}</span>
                <span>@{{lokasi}}</span>
                <span>@{{periode}}</span>
                <span>@{{tanggal_mulai}}</span>
                <span>@{{tanggal_selesai}}</span>
                <span>@{{personil}}</span>
                <span>@{{jumlah_personil}}</span>
                <span>@{{unit_tim}}</span>
                <span>@{{dasar}}</span>
                <span>@{{keterangan}}</span>
            </div>
        </details>
    </div>

    <div
        wire:ignore
        x-data
        x-init="window.SipartaCkeditor.mount($refs.editor, @js($content_html), value => $wire.set('content_html', value))"
        x-on:livewire:navigating.window="window.SipartaCkeditor.destroy($refs.editor)"
    >
        <div x-ref="editor" class="min-h-[420px]"></div>
    </div>

    <textarea wire:model="content_html" class="hidden"></textarea>

    @error('content_html')
        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @enderror
</section>
