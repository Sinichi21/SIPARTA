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
    <div class="space-y-5">

        {{-- Pilihan sumber --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <h2 class="text-lg font-bold text-slate-900">
                    Pilih Sumber Data
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Import SPT dari file lokal atau langsung dari Google Sheets.
                </p>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-3">

                {{-- FILE --}}
                <label
                    class="cursor-pointer rounded-2xl border p-4 transition
                        {{ $sourceType === 'file'
                            ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500'
                            : 'border-slate-200 hover:border-blue-300' }}"
                >
                    <input
                        type="radio"
                        wire:model.live="sourceType"
                        value="file"
                        class="sr-only"
                    >

                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm">
                            📄
                        </div>

                        <div>
                            <div class="font-bold text-slate-900">
                                Upload File
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                CSV atau XLSX dari komputer.
                            </div>
                        </div>
                    </div>
                </label>

                {{-- GOOGLE API --}}
                <label
                    class="cursor-pointer rounded-2xl border p-4 transition
                        {{ $sourceType === 'google_api'
                            ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500'
                            : 'border-slate-200 hover:border-blue-300' }}"
                >
                    <input
                        type="radio"
                        wire:model.live="sourceType"
                        value="google_api"
                        class="sr-only"
                    >

                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm">
                            🔐
                        </div>

                        <div>
                            <div class="font-bold text-slate-900">
                                Google Sheets API
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                Untuk spreadsheet private.
                            </div>

                            <span class="mt-2 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                Direkomendasikan
                            </span>
                        </div>
                    </div>
                </label>

                {{-- PUBLIC --}}
                <label
                    class="cursor-pointer rounded-2xl border p-4 transition
                        {{ $sourceType === 'google_public'
                            ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500'
                            : 'border-slate-200 hover:border-blue-300' }}"
                >
                    <input
                        type="radio"
                        wire:model.live="sourceType"
                        value="google_public"
                        class="sr-only"
                    >

                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm">
                            🔗
                        </div>

                        <div>
                            <div class="font-bold text-slate-900">
                                Public Link
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                Tanpa Google API credential.
                            </div>
                        </div>
                    </div>
                </label>

            </div>

            @error('sourceType')
                <p class="mt-3 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- FILE --}}
        @if($sourceType === 'file')
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 p-10 text-center">

                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <svg
                            class="size-7"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M12 3v12"/>
                            <path d="M7 8l5-5 5 5"/>
                            <path d="M5 21h14a2 2 0 0 0 2-2v-4"/>
                        </svg>
                    </div>

                    <h2 class="text-lg font-bold">
                        Upload file arsip SPT
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Format CSV dan XLSX, maksimal 10 MB.
                    </p>

                    <input
                        type="file"
                        wire:model="file"
                        accept=".csv,.xlsx"
                        class="mx-auto mt-5 block max-w-sm text-sm"
                    >

                    @error('file')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    <div class="mt-6">
                        <button
                            wire:click="readFile"
                            wire:loading.attr="disabled"
                            class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="readFile">
                                Baca File
                            </span>

                            <span wire:loading wire:target="readFile">
                                Membaca...
                            </span>
                        </button>
                    </div>

                </div>
            </div>
        @endif


        {{-- GOOGLE SHEETS API --}}
        @if($sourceType === 'google_api')
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-6">
                    <h2 class="text-lg font-bold">
                        Google Sheets API
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Spreadsheet tetap private. Bagikan spreadsheet
                        ke email Service Account sebagai Viewer.
                    </p>
                </div>

                <div class="space-y-5">

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">
                            URL Google Sheets
                        </span>

                        <input
                            type="url"
                            wire:model="sheetUrl"
                            placeholder="https://docs.google.com/spreadsheets/d/..."
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                        >

                        @error('sheetUrl')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </label>

                    <div>
                        <button
                            type="button"
                            wire:click="loadGoogleSheets"
                            wire:loading.attr="disabled"
                            wire:target="loadGoogleSheets"
                            class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100 disabled:opacity-50"
                        >
                            <span
                                wire:loading.remove
                                wire:target="loadGoogleSheets"
                            >
                                Baca Daftar Sheet
                            </span>

                            <span
                                wire:loading
                                wire:target="loadGoogleSheets"
                            >
                                Menghubungkan...
                            </span>
                        </button>
                    </div>

                    @if(count($availableSheets))
                        <div class="grid gap-4 md:grid-cols-2">

                            <label>
                                <span class="text-sm font-semibold text-slate-700">
                                    Sheet / Tab
                                </span>

                                <select
                                    wire:model="sheetName"
                                    class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                                >
                                    <option value="">
                                        -- Pilih Sheet --
                                    </option>

                                    @foreach($availableSheets as $sheet)
                                        <option value="{{ $sheet['title'] }}">
                                            {{ $sheet['title'] }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('sheetName')
                                    <p class="mt-1 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </label>

                            <label>
                                <span class="text-sm font-semibold text-slate-700">
                                    Range Data
                                </span>

                                <input
                                    type="text"
                                    wire:model="sheetRange"
                                    placeholder="A1:K200"
                                    class="mt-1 w-full rounded-xl border-slate-200 font-mono text-sm uppercase"
                                >

                                <p class="mt-1 text-xs text-slate-500">
                                    Baris pertama dalam range digunakan sebagai header.
                                </p>

                                @error('sheetRange')
                                    <p class="mt-1 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </label>

                        </div>

                        <div class="flex justify-end">
                            <button
                                wire:click="readGoogleSheet"
                                wire:loading.attr="disabled"
                                wire:target="readGoogleSheet"
                                class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                <span
                                    wire:loading.remove
                                    wire:target="readGoogleSheet"
                                >
                                    Ambil Preview Data
                                </span>

                                <span
                                    wire:loading
                                    wire:target="readGoogleSheet"
                                >
                                    Membaca Spreadsheet...
                                </span>
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        @endif


        {{-- PUBLIC GOOGLE SHEETS --}}
        @if($sourceType === 'google_public')
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-5">
                    <h2 class="text-lg font-bold">
                        Public Google Sheets
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Tempel URL spreadsheet yang dapat dibaca tanpa login Google.
                    </p>
                </div>

                <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex gap-3">
                        <div class="text-amber-600">
                            ⚠️
                        </div>

                        <div>
                            <div class="text-sm font-bold text-amber-900">
                                Perhatian keamanan
                            </div>

                            <p class="mt-1 text-xs leading-relaxed text-amber-800">
                                Jangan gunakan metode public untuk data
                                persuratan kantor yang bersifat internal atau
                                sensitif. Gunakan Google Sheets API untuk
                                lingkungan production.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">

                    <label class="md:col-span-2">
                        <span class="text-sm font-semibold text-slate-700">
                            Public Google Sheets URL
                        </span>

                        <input
                            type="url"
                            wire:model="sheetUrl"
                            placeholder="https://docs.google.com/spreadsheets/d/..."
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                        >

                        <p class="mt-1 text-xs text-slate-500">
                            Buka tab yang ingin diimport terlebih dahulu,
                            kemudian copy URL agar gid tab ikut terbaca.
                        </p>

                        @error('sheetUrl')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </label>

                    <label>
                        <span class="text-sm font-semibold text-slate-700">
                            Range Data
                        </span>

                        <input
                            type="text"
                            wire:model="sheetRange"
                            placeholder="A1:K200"
                            class="mt-1 w-full rounded-xl border-slate-200 font-mono text-sm uppercase"
                        >

                        <p class="mt-1 text-xs text-slate-500">
                            Contoh: A1:K100 atau A215:K260.
                        </p>

                        @error('sheetRange')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </label>

                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        wire:click="readGoogleSheet"
                        wire:loading.attr="disabled"
                        wire:target="readGoogleSheet"
                        class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        <span
                            wire:loading.remove
                            wire:target="readGoogleSheet"
                        >
                            Ambil Preview Data
                        </span>

                        <span
                            wire:loading
                            wire:target="readGoogleSheet"
                        >
                            Membaca Spreadsheet...
                        </span>
                    </button>
                </div>

            </div>
        @endif
    </div>
    @elseif($step === 2)
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="mb-5"><h2 class="text-lg font-bold">Mapping Kolom</h2><p class="text-sm text-slate-500">Cocokkan kolom file lama dengan field sistem. Nomor SPT wajib dipetakan. Kolom Jenis Record dapat berisi <strong>normal</strong> atau <strong>koreksi absensi</strong>.</p></div><div class="grid gap-4 md:grid-cols-2">@foreach(['number'=>'Nomor SPT *','letter_date'=>'Tanggal SPT','start_date'=>'Tanggal Mulai','end_date'=>'Tanggal Selesai','subject'=>'Perihal','activity'=>'Jenis Kegiatan','location'=>'Lokasi','personnel_names'=>'Nama Personil','personnel_nips'=>'NIP Personil','description'=>'Keterangan','record_type'=>'Jenis Record'] as $field=>$label)<label class="space-y-1"><span class="text-sm font-semibold text-slate-700">{{ $label }}</span><select wire:model="mapping.{{ $field }}" class="w-full rounded-xl border-slate-200 text-sm"><option value="">-- Tidak dipetakan --</option>@foreach($headers as $header)<option value="{{ $header }}">{{ $header }}</option>@endforeach</select></label>@endforeach</div>@error('mapping.number')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror<div class="mt-6 flex justify-end"><button wire:click="goPreview" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Lanjut Preview</button></div></div>
    @elseif($step === 3)
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="mb-4 flex items-center justify-between"><div><h2 class="text-lg font-bold">Preview Data</h2><p class="text-sm text-slate-500">Menampilkan 10 baris pertama dari {{ count($rows) }} baris.</p></div><button wire:click="$set('step', 2)" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold">Ubah Mapping</button></div><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr>@foreach($headers as $header)<th class="whitespace-nowrap px-3 py-3">{{ $header }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100">@foreach($previewRows as $row)<tr>@foreach($headers as $header)<td class="max-w-52 truncate px-3 py-3">{{ $row[$header] ?? '' }}</td>@endforeach</tr>@endforeach</tbody></table></div><div class="mt-6 flex justify-end"><button wire:click="import" wire:confirm="Import data SPT lama sekarang? Data yang sudah masuk akan tercatat sebagai Import Arsip." class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Import {{ count($rows) }} Baris</button></div></div>
    @elseif($step === 4 && $lastBatch)
        <div class="rounded-2xl border border-emerald-200 bg-white p-8 shadow-sm"><div class="mx-auto max-w-2xl text-center"><div class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 text-2xl text-emerald-700">✓</div><h2 class="mt-4 text-2xl font-bold">Import selesai</h2><p class="mt-2 text-sm text-slate-500">Batch #{{ $lastBatch->id }} · {{ $lastBatch->original_filename }}</p><div class="mt-6 grid gap-3 sm:grid-cols-4"><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Total</p><p class="text-2xl font-bold">{{ $lastBatch->total_rows }}</p></div><div class="rounded-xl bg-emerald-50 p-4"><p class="text-xs text-emerald-700">Diimport</p><p class="text-2xl font-bold text-emerald-700">{{ $lastBatch->imported_rows }}</p></div><div class="rounded-xl bg-amber-50 p-4"><p class="text-xs text-amber-700">Duplikat</p><p class="text-2xl font-bold text-amber-700">{{ $lastBatch->summary['duplicates'] ?? 0 }}</p></div><div class="rounded-xl bg-red-50 p-4"><p class="text-xs text-red-700">Gagal</p><p class="text-2xl font-bold text-red-700">{{ $lastBatch->failed_rows }}</p></div></div><div class="mt-6 flex justify-center gap-3"><button wire:click="restart" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold">Import Lagi</button><a href="{{ route('spt-recap.index') }}" wire:navigate class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Lihat Rekap SPT</a></div></div></div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold">Riwayat Import Terbaru</h2></div><div class="overflow-x-auto"><table class="w-full min-w-[700px] text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">File</th><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Oleh</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Diimport</th><th class="px-4 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($recentBatches as $batch)<tr><td class="px-4 py-3 font-medium">{{ $batch->original_filename }}</td><td class="px-4 py-3">{{ $batch->created_at?->translatedFormat('d M Y H:i') }}</td><td class="px-4 py-3">{{ $batch->uploader?->name ?: '-' }}</td><td class="px-4 py-3">{{ $batch->total_rows }}</td><td class="px-4 py-3">{{ $batch->imported_rows }}</td><td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold">{{ $batch->status }}</span></td></tr>@empty<tr><td colspan="6" class="px-6 py-10 text-center text-slate-500">Belum ada riwayat import.</td></tr>@endforelse</tbody></table></div></div>
</div>
