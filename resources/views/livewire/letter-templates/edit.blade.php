<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('letter-templates.index') }}" wire:navigate class="text-sm text-blue-700">← Template Surat</a>
            <h1 class="mt-2 text-2xl font-bold">Edit Template</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $letterTemplate->name }} · Versi {{ $letterTemplate->version }}
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        @include('livewire.letter-templates._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('letter-templates.index') }}" wire:navigate class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">Kembali</a>
            <button type="submit" class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan Perubahan</button>
        </div>
    </form>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="font-bold">Preview dengan Data SPT</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Kop menggunakan profil yang dipilih pada template, atau profil default sistem.
                </p>
            </div>

            <label class="grid min-w-[280px] gap-1.5 text-xs font-semibold text-slate-700">
                <span>Pilih SPT contoh</span>
                <select wire:model.live="previewLetterId" class="rounded-xl border-slate-200 text-sm">
                    <option value="">Pilih SPT...</option>
                    @foreach($previewLetters as $letter)
                        <option value="{{ $letter->id }}">
                            {{ $letter->number ?: 'Draft #'.$letter->id }} — {{ \Illuminate\Support\Str::limit($letter->subject, 45) }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>

        @if($renderedPreview)
            <div class="overflow-auto rounded-xl border border-slate-200 bg-slate-100 p-4">
                <article class="mx-auto min-h-[1120px] w-full max-w-[794px] bg-white p-12 shadow-sm">
                    <x-letterhead-preview :profile="$letterheadProfile" class="mb-8" />

                    <div class="prose max-w-none">
                        {!! $renderedPreview !!}
                    </div>

                    @if($letterheadProfile?->signatory_name)
                        <div class="ml-auto mt-16 w-64 text-sm text-slate-900">
                            <p>{{ $letterheadProfile->city ?: 'Tempat' }}, {{ $previewLetter?->letter_date?->translatedFormat('d F Y') }}</p>
                            <p class="mt-1">{{ $letterheadProfile->signatory_position ?: 'Pejabat Penandatangan' }}</p>

                            <div class="h-20"></div>

                            <p class="font-semibold underline">{{ $letterheadProfile->signatory_name }}</p>

                            @if($letterheadProfile->signatory_nip)
                                <p>NIP. {{ $letterheadProfile->signatory_nip }}</p>
                            @endif
                        </div>
                    @endif
                </article>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500">
                Pilih SPT untuk melihat preview kop dan hasil placeholder.
            </div>
        @endif
    </section>
</div>
