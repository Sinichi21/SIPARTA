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
