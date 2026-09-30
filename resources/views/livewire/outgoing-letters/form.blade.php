<div class="correspondence-page">
    <nav class="spt-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('outgoing-letters.index') }}" wire:navigate>Surat Keluar</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ $editing ? 'Edit' : 'Tambah' }}</span>
    </nav>

    <x-app.page-heading
        :title="$editing ? 'Edit Draft Surat Keluar' : 'Surat Keluar Baru'"
        description="Siapkan draft. Nomor resmi baru dialokasikan ketika surat diterbitkan."
    >
        <x-slot:actions>
            <button
                type="button"
                wire:click="togglePreview"
                class="spt-action spt-action-view"
            >
                <x-app.icon name="document" />
                {{ $showPreview ? 'Tutup Preview' : 'Preview Cetak' }}
            </button>

            <a
                href="{{ route('outgoing-letters.index') }}"
                wire:navigate
                class="spt-action spt-action-back"
            >
                <x-app.icon name="arrow" class="rotate-180" />
                Kembali
            </a>
        </x-slot:actions>
    </x-app.page-heading>

    <form wire:submit="save" class="correspondence-form">
        <div class="correspondence-intro">
            <span class="stat-icon"><x-app.icon name="document" /></span>
            <div>
                <strong>Draft Surat Keluar</strong>
                <p>
                    Draft tidak menghabiskan nomor surat. Sequence nomor otomatis hanya bergerak saat surat resmi diterbitkan.
                </p>
            </div>
        </div>

        @if($errors->any())
            <div class="correspondence-errors" role="alert">
                <strong>Data belum dapat disimpan.</strong>
                <p>Periksa kembali kolom yang ditandai.</p>
            </div>
        @endif

        <section class="portal-card correspondence-section">
            <header class="correspondence-section-heading">
                <span class="correspondence-section-icon"><x-app.icon name="document" /></span>
                <div>
                    <h2>Pengaturan Surat</h2>
                    <p>Pilih jenis, template, dan profil kop surat.</p>
                </div>
                <span class="correspondence-step">01</span>
            </header>

            <div class="correspondence-fields">
                <label class="correspondence-field">
                    <span class="correspondence-label">Jenis Surat</span>
                    <select wire:model.live="letter_type_id">
                        <option value="">Pilih jenis surat</option>
                        @foreach($letterTypes as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }})</option>
                        @endforeach
                    </select>
                    @error('letter_type_id')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="correspondence-field">
                    <span class="correspondence-label">Template Surat</span>
                    <select wire:model.live="letter_template_id">
                        <option value="">Tanpa template</option>
                        @foreach($templates as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} (v{{ $item->version }})</option>
                        @endforeach
                    </select>
                    @error('letter_template_id')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="correspondence-field">
                    <span class="correspondence-label">Profil Kop</span>
                    <select wire:model.live="letterhead_profile_id">
                        <option value="">Tanpa profil kop</option>
                        @foreach($letterheads as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}{{ $item->is_default ? ' - Default' : '' }}</option>
                        @endforeach
                    </select>
                    @error('letterhead_profile_id')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="correspondence-field">
                    <span class="correspondence-label">Sifat <span class="text-red-500">*</span></span>
                    <select wire:model.live="nature" required>
                        <option value="biasa">Biasa</option>
                        <option value="segera">Segera</option>
                        <option value="penting">Penting</option>
                        <option value="rahasia">Rahasia</option>
                    </select>
                    @error('nature')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
            </div>
        </section>

        <section class="portal-card correspondence-section">
            <header class="correspondence-section-heading">
                <span class="correspondence-section-icon"><x-app.icon name="list" /></span>
                <div>
                    <h2>Penomoran & Tanggal</h2>
                    <p>Nomor final dan tanggal final baru disimpan ketika surat diterbitkan.</p>
                </div>
                <span class="correspondence-step">02</span>
            </header>

            <div class="correspondence-fields">
                <div class="correspondence-field correspondence-field-wide">
                    <span class="correspondence-label">Mode Nomor Surat</span>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="rounded-xl border border-slate-200 p-4">
                            <span class="flex items-start gap-3">
                                <input type="radio" value="auto" wire:model.live="numbering_mode">
                                <span>
                                    <strong class="block text-sm">Otomatis saat Terbit</strong>
                                    <span class="mt-1 block text-xs text-slate-500">
                                        Disarankan. Nomor urut tidak dipakai selama surat masih draft, diverifikasi, atau menunggu persetujuan.
                                    </span>
                                </span>
                            </span>
                        </label>

                        <label class="rounded-xl border border-slate-200 p-4">
                            <span class="flex items-start gap-3">
                                <input type="radio" value="manual" wire:model.live="numbering_mode">
                                <span>
                                    <strong class="block text-sm">Nomor Manual</strong>
                                    <span class="mt-1 block text-xs text-slate-500">
                                        Gunakan hanya untuk kebutuhan administrasi khusus.
                                    </span>
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                @if($numbering_mode === 'manual')
                    <label class="correspondence-field correspondence-field-wide">
                        <span class="correspondence-label">Nomor Surat Manual</span>
                        <input
                            wire:model.live.debounce.300ms="manual_number"
                            type="text"
                            maxlength="255"
                            placeholder="Masukkan nomor surat yang akan digunakan"
                        >
                        @error('manual_number')<span class="text-xs text-red-600">{{ $message }}</span>@enderror

                        <span class="mt-2 block rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-800">
                            Nomor manual akan diperiksa saat draft disimpan dan diperiksa kembali saat penerbitan.
                            Jika nomor sudah digunakan surat lain, surat tidak dapat diterbitkan.
                        </span>
                    </label>
                @endif

                <div class="correspondence-field correspondence-field-wide">
                    <span class="correspondence-label">Tanggal Surat</span>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="rounded-xl border border-slate-200 p-4">
                            <span class="flex items-start gap-3">
                                <input type="radio" value="auto" wire:model.live="date_mode">
                                <span>
                                    <strong class="block text-sm">Tanggal saat Terbit</strong>
                                    <span class="mt-1 block text-xs text-slate-500">
                                        Sistem menggunakan tanggal penerbitan sebagai tanggal surat final.
                                    </span>
                                </span>
                            </span>
                        </label>

                        <label class="rounded-xl border border-slate-200 p-4">
                            <span class="flex items-start gap-3">
                                <input type="radio" value="manual" wire:model.live="date_mode">
                                <span>
                                    <strong class="block text-sm">Tanggal Manual</strong>
                                    <span class="mt-1 block text-xs text-slate-500">
                                        Gunakan tanggal yang ditentukan secara manual.
                                    </span>
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                @if($date_mode === 'manual')
                    <label class="correspondence-field">
                        <span class="correspondence-label">Tanggal Manual</span>
                        <input wire:model.live="manual_letter_date" type="date">
                        @error('manual_letter_date')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                @endif
            </div>
        </section>

        <section class="portal-card correspondence-section">
            <header class="correspondence-section-heading">
                <span class="correspondence-section-icon"><x-app.icon name="users" /></span>
                <div>
                    <h2>Tujuan dan Perihal</h2>
                    <p>Informasi penerima dan pokok surat.</p>
                </div>
                <span class="correspondence-step">03</span>
            </header>

            <div class="correspondence-fields">
                <label class="correspondence-field">
                    <span class="correspondence-label">Tujuan <span class="text-red-500">*</span></span>
                    <input wire:model.live.debounce.300ms="recipient" required type="text" maxlength="255">
                    @error('recipient')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="correspondence-field">
                    <span class="correspondence-label">Klasifikasi</span>
                    <input wire:model.live.debounce.300ms="classification" type="text" maxlength="255">
                </label>

                <label class="correspondence-field correspondence-field-wide">
                    <span class="correspondence-label">Perihal <span class="text-red-500">*</span></span>
                    <textarea wire:model.live.debounce.300ms="subject" required rows="3" maxlength="2000"></textarea>
                    @error('subject')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
            </div>
        </section>

        @if(count($manualPlaceholderNames))
            <section class="portal-card correspondence-section">
                <header class="correspondence-section-heading">
                    <span class="correspondence-section-icon"><x-app.icon name="edit" /></span>
                    <div>
                        <h2>Data Placeholder Tambahan</h2>
                        <p>Field dibuat otomatis dari placeholder template yang belum tersedia pada data sistem.</p>
                    </div>
                    <span class="correspondence-step">04</span>
                </header>

                <div class="correspondence-fields">
                    @foreach($manualPlaceholderNames as $placeholder)
                        <label class="correspondence-field">
                            <span class="correspondence-label">
                                {{ \Illuminate\Support\Str::of($placeholder)->replace(['_', '-'], ' ')->title() }}
                            </span>
                            <input
                                wire:model.live.debounce.300ms="manualFields.{{ $placeholder }}"
                                type="text"
                                maxlength="5000"
                            >
                            <span class="correspondence-help">
                                Placeholder: <code>&#123;&#123; {{ $placeholder }} &#125;&#125;</code>
                            </span>
                            @error('manualFields.'.$placeholder)<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="portal-card correspondence-section">
            <header class="correspondence-section-heading">
                <span class="correspondence-section-icon"><x-app.icon name="edit" /></span>
                <div>
                    <h2>Isi dan Catatan</h2>
                    <p>Isi dapat berasal dari template dan tetap dapat disesuaikan selama masih draft.</p>
                </div>
                <span class="correspondence-step">{{ count($manualPlaceholderNames) ? '05' : '04' }}</span>
            </header>

            <div class="correspondence-fields">
                <label class="correspondence-field correspondence-field-wide">
                    <span class="correspondence-label">Isi Surat</span>
                    <textarea wire:model.live.debounce.500ms="content_html" rows="12"></textarea>
                    <span class="correspondence-help">
                        Preview tidak mengambil nomor urut resmi.
                    </span>
                </label>

                <label class="correspondence-field correspondence-field-wide">
                    <span class="correspondence-label">Catatan Internal</span>
                    <textarea wire:model="notes" rows="4" maxlength="5000"></textarea>
                </label>
            </div>
        </section>

        @if($showPreview)
            @include('livewire.outgoing-letters.partials.a4-preview', [
                'letter' => $previewLetter,
                'renderedBody' => $previewBody,
                'missingPlaceholders' => $missingPlaceholders,
                'previewMode' => true,
            ])
        @endif

        <footer class="portal-card correspondence-footer">
            <div>
                <strong>Simpan sebagai draft</strong>
                <p>
                    Menyimpan draft tidak menghasilkan nomor surat resmi.
                </p>
            </div>

            <div class="correspondence-actions">
                <button
                    type="button"
                    wire:click="togglePreview"
                    class="spt-action spt-action-view"
                >
                    {{ $showPreview ? 'Tutup Preview' : 'Preview Cetak' }}
                </button>

                <a href="{{ route('outgoing-letters.index') }}" wire:navigate class="spt-action spt-action-back">
                    Batal
                </a>

                <button
                    type="submit"
                    class="spt-action spt-action-primary"
                    wire:loading.attr="disabled"
                >
                    <x-app.icon name="document" />
                    <span>{{ $editing ? 'Simpan Perubahan' : 'Simpan Draft' }}</span>
                </button>
            </div>
        </footer>
    </form>
</div>
