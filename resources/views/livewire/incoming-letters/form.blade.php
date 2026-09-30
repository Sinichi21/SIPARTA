<div class="correspondence-page">
    <nav class="spt-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('incoming-letters.index') }}" wire:navigate>Surat Masuk</a><span aria-hidden="true">/</span><span aria-current="page">{{ $editing ? 'Edit' : 'Tambah' }}</span></nav>

    <x-app.page-heading :title="$editing ? 'Edit Surat Masuk' : 'Catat Surat Masuk'" description="Kelola pencatatan dan informasi surat yang diterima.">
        <x-slot:actions><a href="{{ route('incoming-letters.index') }}" wire:navigate class="spt-action spt-action-back"><x-app.icon name="arrow" class="rotate-180" /> Kembali ke Surat Masuk</a></x-slot:actions>
    </x-app.page-heading>

    <form wire:submit="save" class="correspondence-form">
        <div class="correspondence-intro"><span class="stat-icon"><x-app.icon name="document" /></span><div><strong>Form Pencatatan Surat Masuk</strong><p>Kolom bertanda <span class="text-red-500">*</span> wajib diisi. File surat asli bersifat opsional.</p></div></div>

        @if($errors->any())<div class="correspondence-errors" role="alert"><strong>Data belum dapat disimpan.</strong><p>Periksa kolom yang ditandai dan lengkapi informasi yang diperlukan.</p></div>@endif

        <section class="portal-card correspondence-section">
            <header class="correspondence-section-heading"><span class="correspondence-section-icon"><x-app.icon name="document" /></span><div><h2>Identitas Surat</h2><p>Nomor dan tanggal untuk pencatatan surat masuk.</p></div><span class="correspondence-step">01</span></header>
            <div class="correspondence-fields">
                <label class="correspondence-field"><span class="correspondence-label">Nomor Agenda <span class="text-red-500">*</span></span><input wire:model="agenda_number" required type="text" maxlength="255">@error('agenda_number')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                <label class="correspondence-field"><span class="correspondence-label">Nomor Surat</span><input wire:model="number" type="text" maxlength="255">@error('number')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                <label class="correspondence-field"><span class="correspondence-label">Tanggal Surat</span><input wire:model="letter_date" type="date">@error('letter_date')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                <label class="correspondence-field"><span class="correspondence-label">Tanggal Diterima <span class="text-red-500">*</span></span><input wire:model="received_date" required type="date">@error('received_date')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
            </div>
        </section>

        <section class="portal-card correspondence-section">
            <header class="correspondence-section-heading"><span class="correspondence-section-icon"><x-app.icon name="users" /></span><div><h2>Asal dan Perihal</h2><p>Informasi pengirim, penerima, dan pokok surat.</p></div><span class="correspondence-step">02</span></header>
            <div class="correspondence-fields">
                <label class="correspondence-field"><span class="correspondence-label">Asal Surat <span class="text-red-500">*</span></span><input wire:model="sender" required type="text" maxlength="255">@error('sender')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                <label class="correspondence-field"><span class="correspondence-label">Tujuan</span><input wire:model="destination" type="text" maxlength="255">@error('destination')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                <label class="correspondence-field correspondence-field-wide"><span class="correspondence-label">Perihal <span class="text-red-500">*</span></span><textarea wire:model="subject" required rows="3" maxlength="2000"></textarea>@error('subject')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
            </div>
        </section>

        <section class="portal-card correspondence-section">
            <header class="correspondence-section-heading"><span class="correspondence-section-icon"><x-app.icon name="list" /></span><div><h2>Informasi Tambahan</h2><p>Klasifikasi, sifat, lampiran, dan catatan administrasi.</p></div><span class="correspondence-step">03</span></header>
            <div class="correspondence-fields">
                <label class="correspondence-field"><span class="correspondence-label">Klasifikasi</span><input wire:model="classification" type="text" maxlength="255"></label>
                <label class="correspondence-field"><span class="correspondence-label">Sifat <span class="text-red-500">*</span></span><select wire:model="nature" required><option value="biasa">Biasa</option><option value="segera">Segera</option><option value="penting">Penting</option><option value="rahasia">Rahasia</option></select></label>
                <label class="correspondence-field"><span class="correspondence-label">Keterangan Lampiran</span><input wire:model="attachment_note" type="text" maxlength="255" placeholder="Contoh: 2 berkas"></label>
                <label class="correspondence-field correspondence-field-wide"><span class="correspondence-label">Catatan</span><textarea wire:model="notes" rows="4" maxlength="5000"></textarea></label>
            </div>
        </section>

        <section class="portal-card correspondence-section">
            <header class="correspondence-section-heading"><span class="correspondence-section-icon"><x-app.icon name="archive" /></span><div><h2>File Surat Asli</h2><p>Unggah file surat asli bila tersedia. Tidak wajib.</p></div><span class="correspondence-step">04</span></header>
            <div class="correspondence-fields">
                @if($editing && $letter->original_file_path)
                    <div class="correspondence-field correspondence-field-wide">
                        <div class="document-card"><x-app.icon name="document" /><div class="min-w-0 flex-1"><strong class="block break-words text-sm">{{ $letter->original_file_name ?: 'File surat masuk' }}</strong><span class="text-xs text-slate-500">File saat ini</span></div></div>
                        <label class="mt-3 inline-flex items-center gap-2 text-xs text-slate-600"><input wire:model="removeAttachment" type="checkbox"> Hapus file lama saat menyimpan</label>
                    </div>
                @endif

                <label class="correspondence-field correspondence-field-wide">
                    <span class="correspondence-label">{{ $editing && $letter->original_file_path ? 'Ganti File' : 'Lampirkan File' }} <span class="font-normal text-slate-400">(opsional)</span></span>
                    <input wire:model="attachmentUpload" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                    <span class="correspondence-help">PDF, Word, Excel, JPG, atau PNG. Maksimal 10 MB.</span>
                    @error('attachmentUpload')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
            </div>
        </section>

        <footer class="portal-card correspondence-footer"><div><strong>Pencatatan surat</strong><p>Pastikan data surat sesuai dokumen asli.</p></div><div class="correspondence-actions"><a href="{{ route('incoming-letters.index') }}" wire:navigate class="spt-action spt-action-back">Batal</a><button type="submit" class="spt-action spt-action-primary" wire:loading.attr="disabled"><x-app.icon name="document" /><span>{{ $editing ? 'Simpan Perubahan' : 'Simpan Surat Masuk' }}</span></button></div></footer>
    </form>
</div>
