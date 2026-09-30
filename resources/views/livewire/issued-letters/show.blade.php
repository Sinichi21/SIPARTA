<div class="space-y-6">
    <x-app.page-heading
        title="Detail Surat Terbit"
        :description="$letter->number"
    >
        <x-slot:actions>
            <a href="{{ route('issued-letters.index') }}" wire:navigate class="spt-action spt-action-back">Kembali</a>
            <a href="{{ route('outgoing-letters.show', $letter->outgoingLetter) }}" wire:navigate class="spt-action spt-action-view">Lihat Surat Keluar</a>
        </x-slot:actions>
    </x-app.page-heading>

    <section class="portal-card">
        <div class="mb-5">
            <span class="status-badge status-success">Terbit</span>
        </div>

        <dl class="grid gap-5 md:grid-cols-2">
            <div><dt class="text-xs text-slate-500">Nomor Surat</dt><dd class="mt-1 font-semibold">{{ $letter->number }}</dd></div>
            <div><dt class="text-xs text-slate-500">Tanggal Surat</dt><dd class="mt-1">{{ $letter->letter_date?->translatedFormat('d F Y') }}</dd></div>
            <div><dt class="text-xs text-slate-500">Jenis Surat</dt><dd class="mt-1">{{ $letter->letterType?->name ?: '-' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Tujuan</dt><dd class="mt-1">{{ $letter->recipient }}</dd></div>
            <div class="md:col-span-2"><dt class="text-xs text-slate-500">Perihal</dt><dd class="mt-1 font-semibold">{{ $letter->subject }}</dd></div>
            <div><dt class="text-xs text-slate-500">Penandatangan</dt><dd class="mt-1">{{ $letter->signatory_name ?: '-' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Jabatan</dt><dd class="mt-1">{{ $letter->signatory_position ?: '-' }}</dd></div>
            <div><dt class="text-xs text-slate-500">NIP</dt><dd class="mt-1">{{ $letter->signatory_nip ?: '-' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Waktu Terbit</dt><dd class="mt-1">{{ $letter->issued_at?->translatedFormat('d M Y, H:i') }}</dd></div>
            <div><dt class="text-xs text-slate-500">Diterbitkan oleh</dt><dd class="mt-1">{{ $letter->issuer?->name ?: '-' }}</dd></div>
        </dl>
    </section>
</div>
