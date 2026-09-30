<div class="correspondence-page">
    <nav class="spt-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('outgoing-letters.index') }}" wire:navigate>Surat Keluar</a><span aria-hidden="true">/</span><span aria-current="page">{{ $editing ? 'Edit' : 'Tambah' }}</span></nav>
    <x-app.page-heading :title="$editing ? 'Edit Draft Surat Keluar' : 'Surat Keluar Baru'" description="Siapkan draft surat keluar dengan format dan informasi yang lengkap.">
        <x-slot:actions><a href="{{ route('outgoing-letters.index') }}" wire:navigate class="spt-action spt-action-back"><x-app.icon name="arrow" class="rotate-180" /> Kembali ke Surat Keluar</a></x-slot:actions>
    </x-app.page-heading>
    <form wire:submit="save" class="correspondence-form">
        <div class="correspondence-intro"><span class="stat-icon"><x-app.icon name="document" /></span><div><strong>Form Draft Surat Keluar</strong><p>Kolom bertanda <span class="text-red-500">*</span> wajib diisi. Periksa data sebelum menyimpan.</p></div></div>
        @if($errors->any())<div class="correspondence-errors" role="alert"><strong>Data belum dapat disimpan.</strong><p>Periksa kolom yang ditandai dan lengkapi informasi yang diperlukan.</p></div>@endif
        <section class="portal-card correspondence-section" aria-labelledby="correspondence-section-1">
            <header class="correspondence-section-heading"><span class="correspondence-section-icon"><x-app.icon name="document" /></span><div><h2 id="correspondence-section-1">Pengaturan Surat</h2><p>Pilih jenis surat dan format dokumen yang akan digunakan.</p></div><span class="correspondence-step">01</span></header>
            <div class="correspondence-fields">
<label class="correspondence-field"><span class="correspondence-label">Jenis Surat</span>
<select wire:model="letter_type_id">
                    <option value="">Pilih jenis surat</option>
                    @foreach($letterTypes as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
                @error('letter_type_id')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
            </label>
<label class="correspondence-field"><span class="correspondence-label">Template Surat</span>
<select wire:model.live="letter_template_id">
                    <option value="">Tanpa template</option>
                    @foreach($templates as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} (v{{ $item->version }})</option>
                    @endforeach
                </select>
            @error('letter_template_id')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
</label>
<label class="correspondence-field"><span class="correspondence-label">Profil Kop</span>
<select wire:model="letterhead_profile_id">
                    <option value="">Tanpa profil kop</option>
                    @foreach($letterheads as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}{{ $item->is_default ? ' - Default' : '' }}</option>
                    @endforeach
                </select>
            @error('letterhead_profile_id')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
</label>
<label class="correspondence-field"><span class="correspondence-label">Sifat <span class="text-red-500" aria-hidden="true">*</span></span>
<select wire:model="nature" required>
                    <option value="biasa">Biasa</option>
                    <option value="segera">Segera</option>
                    <option value="penting">Penting</option>
                    <option value="rahasia">Rahasia</option>
                </select>
            @error('nature')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
</label>
            </div>
        </section>
        <section class="portal-card correspondence-section" aria-labelledby="correspondence-section-2">
            <header class="correspondence-section-heading"><span class="correspondence-section-icon"><x-app.icon name="users" /></span><div><h2 id="correspondence-section-2">Tujuan dan Perihal</h2><p>Tentukan penerima dan pokok surat yang akan dikirim.</p></div><span class="correspondence-step">02</span></header>
            <div class="correspondence-fields">
<label class="correspondence-field"><span class="correspondence-label">Tujuan <span class="text-red-500" aria-hidden="true">*</span></span>
<input wire:model="recipient" placeholder="Nama penerima atau instansi tujuan" required type="text" maxlength="255">
                @error('recipient')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
            </label>
<label class="correspondence-field"><span class="correspondence-label">Klasifikasi</span>
<input wire:model="classification" placeholder="Contoh: kode klasifikasi surat" type="text" maxlength="255">
            @error('classification')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
</label>
<label class="correspondence-field correspondence-field-wide"><span class="correspondence-label">Perihal <span class="text-red-500" aria-hidden="true">*</span></span>
<textarea wire:model="subject" placeholder="Ringkasan pokok atau tujuan surat" required rows="3" maxlength="2000"></textarea>
                @error('subject')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
            </label>
            </div>
        </section>
        <section class="portal-card correspondence-section" aria-labelledby="correspondence-section-3">
            <header class="correspondence-section-heading"><span class="correspondence-section-icon"><x-app.icon name="edit" /></span><div><h2 id="correspondence-section-3">Isi dan Catatan</h2><p>Susun isi surat dan lengkapi catatan administrasi.</p></div><span class="correspondence-step">03</span></header>
            <div class="correspondence-fields">
<label class="correspondence-field correspondence-field-wide"><span class="correspondence-label">Isi Surat</span>
<textarea wire:model="content_html" rows="12" placeholder="Tuliskan isi surat atau gunakan template yang tersedia..."></textarea>
                <span class="correspondence-help">Periksa kembali isi surat sebelum menyimpan draft.</span>
            @error('content_html')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
</label>
<label class="correspondence-field correspondence-field-wide"><span class="correspondence-label">Catatan Internal</span>
<textarea wire:model="notes" placeholder="Catatan tambahan untuk administrasi internal" rows="4" maxlength="5000"></textarea>
            @error('notes')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
</label>
            </div>
        </section>
        <footer class="portal-card correspondence-footer"><div><strong>Simpan sebagai draft</strong><p>Draft dapat diperiksa kembali sebelum surat diterbitkan.</p></div><div class="correspondence-actions"><a href="{{ route('outgoing-letters.index') }}" wire:navigate class="spt-action spt-action-back">Batal</a><button type="submit" class="spt-action spt-action-primary" wire:loading.attr="disabled" wire:target="save"><x-app.icon name="document" /><span wire:loading.remove wire:target="save">{{ $editing ? 'Simpan Perubahan' : 'Simpan Draft' }}</span><span wire:loading wire:target="save">Menyimpan...</span></button></div></footer>
    </form>
</div>
