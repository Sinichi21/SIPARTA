<div class="correspondence-page">
    <nav class="spt-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('incoming-letters.index') }}" wire:navigate>Surat Masuk</a><span aria-hidden="true">/</span><span aria-current="page">{{ $editing ? 'Edit' : 'Tambah' }}</span></nav>
    <x-app.page-heading :title="$editing ? 'Edit Surat Masuk' : 'Catat Surat Masuk'" description="Kelola pencatatan dan informasi surat yang diterima.">
        <x-slot:actions><a href="{{ route('incoming-letters.index') }}" wire:navigate class="spt-action spt-action-back"><x-app.icon name="arrow" class="rotate-180" /> Kembali ke Surat Masuk</a></x-slot:actions>
    </x-app.page-heading>
    <form wire:submit="save" class="correspondence-form">
        <div class="correspondence-intro"><span class="stat-icon"><x-app.icon name="document" /></span><div><strong>Form Pencatatan Surat Masuk</strong><p>Kolom bertanda <span class="text-red-500">*</span> wajib diisi. Periksa data sebelum menyimpan.</p></div></div>
        @if($errors->any())<div class="correspondence-errors" role="alert"><strong>Data belum dapat disimpan.</strong><p>Periksa kolom yang ditandai dan lengkapi informasi yang diperlukan.</p></div>@endif
        <section class="portal-card correspondence-section" aria-labelledby="correspondence-section-1">
            <header class="correspondence-section-heading"><span class="correspondence-section-icon"><x-app.icon name="document" /></span><div><h2 id="correspondence-section-1">Identitas Surat</h2><p>Nomor dan tanggal untuk pencatatan surat masuk.</p></div><span class="correspondence-step">01</span></header>
            <div class="correspondence-fields">
<label class="correspondence-field"><span class="correspondence-label">Nomor Agenda <span class="text-red-500" aria-hidden="true">*</span></span>
<input wire:model="agenda_number" required type="text" maxlength="255">
                @error('agenda_number')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
            </label>
<label class="correspondence-field"><span class="correspondence-label">Nomor Surat</span>
<input wire:model="number" placeholder="Nomor yang tertera pada surat" type="text" maxlength="255">
                @error('number')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
            </label>
<label class="correspondence-field"><span class="correspondence-label">Tanggal Surat</span>
<input wire:model="letter_date" type="date">
                @error('letter_date')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
            </label>
<label class="correspondence-field"><span class="correspondence-label">Tanggal Diterima <span class="text-red-500" aria-hidden="true">*</span></span>
<input wire:model="received_date" required type="date">
                @error('received_date')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
            </label>
            </div>
        </section>
        <section class="portal-card correspondence-section" aria-labelledby="correspondence-section-2">
            <header class="correspondence-section-heading"><span class="correspondence-section-icon"><x-app.icon name="users" /></span><div><h2 id="correspondence-section-2">Asal dan Perihal</h2><p>Informasi pengirim, penerima, dan pokok surat.</p></div><span class="correspondence-step">02</span></header>
            <div class="correspondence-fields">
<label class="correspondence-field"><span class="correspondence-label">Asal Surat <span class="text-red-500" aria-hidden="true">*</span></span>
<input wire:model="sender" placeholder="Contoh: Dinas Komunikasi dan Informatika" required type="text" maxlength="255">
                @error('sender')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
            </label>
<label class="correspondence-field"><span class="correspondence-label">Tujuan</span>
<input wire:model="destination" placeholder="Contoh: Kepala Balai" type="text" maxlength="255">
                @error('destination')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
            </label>
<label class="correspondence-field correspondence-field-wide"><span class="correspondence-label">Perihal <span class="text-red-500" aria-hidden="true">*</span></span>
<textarea wire:model="subject" placeholder="Ringkasan pokok atau tujuan surat" required rows="3" maxlength="2000"></textarea>
                @error('subject')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
            </label>
            </div>
        </section>
        <section class="portal-card correspondence-section" aria-labelledby="correspondence-section-3">
            <header class="correspondence-section-heading"><span class="correspondence-section-icon"><x-app.icon name="list" /></span><div><h2 id="correspondence-section-3">Informasi Tambahan</h2><p>Lengkapi klasifikasi, sifat, dan catatan pendukung.</p></div><span class="correspondence-step">03</span></header>
            <div class="correspondence-fields">
<label class="correspondence-field"><span class="correspondence-label">Klasifikasi</span>
<input wire:model="classification" placeholder="Contoh: kode klasifikasi surat" type="text" maxlength="255">
                @error('classification')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
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
<label class="correspondence-field"><span class="correspondence-label">Lampiran</span>
<input wire:model="attachment_note" type="text" maxlength="255" placeholder="Contoh: 2 berkas">
            @error('attachment_note')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
</label>
<label class="correspondence-field correspondence-field-wide"><span class="correspondence-label">Catatan</span>
<textarea wire:model="notes" placeholder="Catatan tambahan untuk administrasi internal" rows="4" maxlength="5000"></textarea>
            @error('notes')<span class="text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
</label>
            </div>
        </section>
        <footer class="portal-card correspondence-footer"><div><strong>Pencatatan surat</strong><p>Pastikan nomor, asal, dan tanggal penerimaan sudah sesuai.</p></div><div class="correspondence-actions"><a href="{{ route('incoming-letters.index') }}" wire:navigate class="spt-action spt-action-back">Batal</a><button type="submit" class="spt-action spt-action-primary" wire:loading.attr="disabled" wire:target="save"><x-app.icon name="document" /><span wire:loading.remove wire:target="save">{{ $editing ? 'Simpan Perubahan' : 'Simpan Surat Masuk' }}</span><span wire:loading wire:target="save">Menyimpan...</span></button></div></footer>
    </form>
</div>
