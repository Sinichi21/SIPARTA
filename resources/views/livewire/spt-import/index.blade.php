<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            Import SPT Lama
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Masukkan arsip SPT lama tanpa harus membuat ulang surat melalui template.
        </p> 
        <p class="mt-2 text-xs text-blue-700">
            Nilai personil ALL PEGAWAI, SEMUA PEGAWAI, atau SELURUH PEGAWAI
            akan disimpan sebagai cakupan seluruh pegawai dan tidak dibuat
            sebagai data personil.
        </p>
    </div>

    <div class="grid grid-cols-4 gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        @foreach([
            1 => 'Sumber Data',
            2 => 'Mapping Kolom',
            3 => 'Preview',
            4 => 'Selesai',
        ] as $number => $label)
            <div class="rounded-xl px-3 py-3 text-center text-sm font-semibold {{ $step >= $number ? 'bg-blue-600 text-white' : 'bg-slate-50 text-slate-400' }}">
                {{ $number }}. {{ $label }}
            </div>
        @endforeach
    </div>
    <p class="mt-2 text-xs text-blue-700">
        Nilai personil ALL PEGAWAI, SEMUA PEGAWAI, atau SELURUH PEGAWAI
        akan disimpan sebagai cakupan seluruh pegawai dan tidak dibuat
        sebagai data personil.
    </p>

    @if($step === 1)
    @if($step === 1)
    <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
        <h2 class="text-lg font-bold">Sumber Import SPT</h2>
        @if(session('google_error')) <p role="alert" class="text-red-700">{{ session('google_error') }}</p> @endif
        @if(session('google_success')) <p role="status" class="text-green-700">{{ session('google_success') }}</p> @endif
        <label class="block text-sm font-semibold" for="sourceType">Sumber data</label>
        <select id="sourceType" wire:model.live="sourceType" class="w-full rounded-xl border-slate-300">
            <option value="file">CSV / XLSX</option>
            <option value="google_api">Google Sheets (akun Google terhubung)</option>
            <option value="google_public">Google Sheets (public link)</option>
        </select>
        @if($sourceType === 'file')
            <input type="file" wire:model="file" accept=".csv,.xlsx" class="block w-full">
            @error('file') <p role="alert" class="text-red-700">{{ $message }}</p> @enderror
            <button type="button" wire:click="readFile" wire:loading.attr="disabled" class="rounded-lg bg-blue-700 px-4 py-2 text-white">Baca File</button>
        @else
            @if($sourceType === 'google_api')
                @if($googleConnected)
                    <p class="text-sm text-green-700">Akun Google sudah terhubung.</p>
                    <form method="POST" action="{{ route('spt-import.google.disconnect') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-400 px-4 py-2 text-sm">Putuskan koneksi</button>
                    </form>
                @else
                    <a href="{{ route('spt-import.google.redirect') }}" class="inline-block rounded-lg bg-blue-700 px-4 py-2 text-white">Hubungkan akun Google</a>
                @endif
            @else
                <p class="text-sm text-amber-700">Hanya untuk spreadsheet publik tanpa data kantor yang sensitif.</p>
            @endif
            <label class="block text-sm font-semibold">URL Google Sheets
                <input type="url" wire:model="sheetUrl" placeholder="https://docs.google.com/spreadsheets/d/.../edit#gid=0" class="mt-1 block w-full rounded-xl border-slate-300">
            </label>
            @error('sheetUrl') <p role="alert" class="text-red-700">{{ $message }}</p> @enderror
            @if($sourceType === 'google_api' && $googleConnected)
                <button type="button" wire:click="loadGoogleSheets" wire:loading.attr="disabled" class="rounded-lg border border-blue-600 px-4 py-2 text-blue-700">Baca daftar tab</button>
                @if($availableSheets)
                    <label class="block text-sm font-semibold">Tab
                        <select wire:model="sheetName" class="mt-1 block w-full rounded-xl border-slate-300">
                            @foreach($availableSheets as $sheet)
                                <option value="{{ $sheet['title'] }}">{{ $sheet['title'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            @endif
            @if($sourceType === 'google_public' || ($googleConnected && count($availableSheets) > 0))
                <label class="block text-sm font-semibold">Range (baris pertama = header)
                    <input type="text" wire:model="sheetRange" placeholder="A1:K200" class="mt-1 block w-full rounded-xl border-slate-300">
                </label>
                @error('sheetRange') <p role="alert" class="text-red-700">{{ $message }}</p> @enderror
                <button type="button" wire:click="readGoogleSheet" wire:loading.attr="disabled" class="rounded-lg bg-blue-700 px-4 py-2 text-white">Baca spreadsheet</button>
            @endif
        @endif
    </section>

    @elseif($step === 2)
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="mb-5"><h2 class="text-lg font-bold">Mapping Kolom</h2><p class="text-sm text-slate-500">Cocokkan kolom file lama dengan field sistem. Nomor SPT wajib dipetakan. Kolom Jenis Record dapat berisi <strong>normal</strong> atau <strong>koreksi absensi</strong>.</p></div><div class="grid gap-4 md:grid-cols-2">@foreach(['number'=>'Nomor SPT *','letter_date'=>'Tanggal SPT','start_date'=>'Tanggal Mulai','end_date'=>'Tanggal Selesai','subject'=>'Perihal','activity'=>'Jenis Kegiatan','location'=>'Lokasi','personnel_names'=>'Nama Personil','personnel_nips'=>'NIP Personil','description'=>'Keterangan','record_type'=>'Jenis Record'] as $field=>$label)<label class="space-y-1"><span class="text-sm font-semibold text-slate-700">{{ $label }}</span><select wire:model="mapping.{{ $field }}" class="w-full rounded-xl border-slate-200 text-sm"><option value="">-- Tidak dipetakan --</option>@foreach($headers as $header)<option value="{{ $header }}">{{ $header }}</option>@endforeach</select></label>@endforeach</div>@error('mapping.number')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror<div class="mt-6 flex justify-end"><button wire:click="goPreview" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Lanjut Preview</button></div></div>
    @elseif($step === 3)
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="mb-4 flex items-center justify-between"><div><h2 class="text-lg font-bold">Preview Data</h2><p class="text-sm text-slate-500">Menampilkan 10 baris pertama dari {{ count($rows) }} baris.</p></div><button wire:click="$set('step', 2)" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold">Ubah Mapping</button></div><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr>@foreach($headers as $header)<th class="whitespace-nowrap px-3 py-3">{{ $header }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100">@foreach($previewRows as $row)<tr>@foreach($headers as $header)<td class="max-w-52 truncate px-3 py-3">{{ $row[$header] ?? '' }}</td>@endforeach</tr>@endforeach</tbody></table></div><div class="mt-6 flex justify-end"><button wire:click="import" wire:confirm="Import data SPT lama sekarang? Data yang sudah masuk akan tercatat sebagai Import Arsip." class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Import {{ count($rows) }} Baris</button></div></div>
    @elseif($step === 4 && $lastBatch)
        <div class="rounded-2xl border border-emerald-200 bg-white p-8 shadow-sm"><div class="mx-auto max-w-2xl text-center"><div class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 text-2xl text-emerald-700">✓</div><h2 class="mt-4 text-2xl font-bold">Import selesai</h2><p class="mt-2 text-sm text-slate-500">Batch #{{ $lastBatch->id }} · {{ $lastBatch->original_filename }}</p><div class="mt-6 grid gap-3 sm:grid-cols-4"><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Total</p><p class="text-2xl font-bold">{{ $lastBatch->total_rows }}</p></div><div class="rounded-xl bg-emerald-50 p-4"><p class="text-xs text-emerald-700">Diimport</p><p class="text-2xl font-bold text-emerald-700">{{ $lastBatch->imported_rows }}</p></div><div class="rounded-xl bg-amber-50 p-4"><p class="text-xs text-amber-700">Duplikat</p><p class="text-2xl font-bold text-amber-700">{{ $lastBatch->summary['duplicates'] ?? 0 }}</p></div><div class="rounded-xl bg-red-50 p-4"><p class="text-xs text-red-700">Gagal</p><p class="text-2xl font-bold text-red-700">{{ $lastBatch->failed_rows }}</p></div></div><div class="mt-6 flex justify-center gap-3"><button wire:click="restart" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold">Import Lagi</button><a href="{{ route('spt-recap.index') }}" wire:navigate class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Lihat Rekap SPT</a></div></div></div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold">Riwayat Import Terbaru</h2></div><div class="overflow-x-auto"><table class="w-full min-w-[700px] text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">File</th><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Oleh</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Diimport</th><th class="px-4 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($recentBatches as $batch)<tr><td class="px-4 py-3 font-medium">{{ $batch->original_filename }}</td><td class="px-4 py-3">{{ $batch->created_at?->translatedFormat('d M Y H:i') }}</td><td class="px-4 py-3">{{ $batch->uploader?->name ?: '-' }}</td><td class="px-4 py-3">{{ $batch->total_rows }}</td><td class="px-4 py-3">{{ $batch->imported_rows }}</td><td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold">{{ $batch->status }}</span></td></tr>@empty<tr><td colspan="6" class="px-6 py-10 text-center text-slate-500">Belum ada riwayat import.</td></tr>@endforelse</tbody></table></div></div>
</div>
