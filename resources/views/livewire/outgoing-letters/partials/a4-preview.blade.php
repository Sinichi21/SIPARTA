<section class="portal-card correspondence-section">
    <header class="correspondence-section-heading">
        <span class="correspondence-section-icon"><x-app.icon name="document" /></span>
        <div>
            <h2>Preview Cetak</h2>
            <p>
                Preview tidak mengambil nomor sequence. Nomor resmi baru dialokasikan saat surat diterbitkan.
            </p>
        </div>
    </header>

    @if(count($missingPlaceholders))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-800">
            <strong>Placeholder belum terisi:</strong>
            @foreach($missingPlaceholders as $placeholder)
                <code>&#123;&#123; {{ $placeholder }} &#125;&#125;</code>@if(! $loop->last), @endif
            @endforeach
        </div>
    @endif

    <div
        x-data="{ mode: 'fit' }"
        class="rounded-xl border border-slate-200 bg-slate-50 p-4"
    >
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pratinjau Surat</p>
                <p class="mt-1 text-sm font-medium text-slate-800">
                    {{ $letter->number ?: ($letter->numbering_mode === 'manual' ? ($letter->manual_number ?: 'Nomor manual belum diisi') : 'Nomor otomatis saat terbit') }}
                </p>
            </div>

            <div class="inline-flex rounded-lg border border-slate-300 bg-white p-1">
                <button
                    type="button"
                    x-on:click="mode = 'fit'"
                    x-bind:class="mode === 'fit' ? 'bg-slate-900 text-white' : 'text-slate-600'"
                    class="rounded-md px-3 py-1.5 text-xs font-medium"
                >
                    Fit
                </button>
                <button
                    type="button"
                    x-on:click="mode = 'real'"
                    x-bind:class="mode === 'real' ? 'bg-slate-900 text-white' : 'text-slate-600'"
                    class="rounded-md px-3 py-1.5 text-xs font-medium"
                >
                    Real A4
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <div
                class="mx-auto origin-top bg-white text-black shadow-xl ring-1 ring-slate-300"
                x-bind:style="mode === 'fit'
                    ? 'width:210mm;min-height:297mm;padding:16mm;zoom:.68;'
                    : 'width:210mm;min-height:297mm;padding:16mm;zoom:1;'"
            >
                @if($letter->letterheadProfile)
                    <x-official-letterhead :profile="$letter->letterheadProfile" />
                @endif

                <div
                    style="font-family:'Times New Roman',Times,serif;font-size:12pt;line-height:1.45;"
                >
                    {!! $renderedBody !!}
                </div>

                @if(! $letter->content_html)
                    <div style="font-family:'Times New Roman',Times,serif;font-size:12pt;color:#777;">
                        Belum ada isi surat.
                    </div>
                @endif

                @if($previewMode ?? false)
                    <div style="margin-top:12mm;border-top:.7px solid #aaa;padding-top:3mm;font-family:Arial,sans-serif;font-size:8pt;color:#666;">
                        PRATINJAU — dokumen belum diterbitkan, belum memiliki nomor resmi, dan belum masuk Register Surat Terbit.
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
