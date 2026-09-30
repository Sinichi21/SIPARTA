<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview Surat Keluar {{ $letter->number ?: 'Draft '.$letter->id }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm; }
        * { box-sizing: border-box; }
        body { margin:0; background:#eef2f7; color:#111827; font-family:"Times New Roman",Times,serif; }
        .toolbar { position:sticky; top:0; z-index:20; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 18px; background:#0f2f57; color:#fff; font-family:Arial,sans-serif; }
        .toolbar strong { display:block; font-size:13px; }
        .toolbar span { display:block; margin-top:3px; color:#bdd6f4; font-size:11px; }
        .toolbar button,.toolbar a { border:1px solid #ffffff35; border-radius:7px; padding:8px 11px; background:#ffffff10; color:#fff; font-size:12px; font-weight:600; text-decoration:none; cursor:pointer; }
        .missing { width:210mm; margin:18px auto -8px; border:1px solid #f1d38b; border-radius:8px; padding:10px 12px; background:#fff9e8; color:#805f17; font:11px/1.6 Arial,sans-serif; }
        .page { width:210mm; min-height:297mm; margin:24px auto; padding:18mm; background:#fff; box-shadow:0 8px 30px rgba(15,23,42,.12); }
        .letterhead { border-bottom:4px double #111; padding-bottom:8px; margin-bottom:24px; text-align:center; }
        .letterhead h1 { margin:0; font-size:18px; line-height:1.15; text-transform:uppercase; }
        .letterhead p { margin:3px 0 0; font-size:10px; line-height:1.3; }
        .content { font-size:12pt; line-height:1.5; }
        .content table { width:100%; border-collapse:collapse; }
        .content td,.content th { border:1px solid #111; padding:5px; vertical-align:top; }
        @media print {
            body { background:#fff; }
            .toolbar,.missing { display:none !important; }
            .page { width:auto; min-height:auto; margin:0; padding:0; box-shadow:none; }
        }
        @if($forPdf ?? false)
            body { background: #fff; }
            .page { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
        @endif
    </style>
</head>
<body>
    @unless($forPdf ?? false)
    <div class="toolbar">
        <div>
            <strong>{{ $letter->number ?: 'Draft #'.$letter->id }}</strong>
            <span>Preview Surat Keluar</span>
        </div>
        <div>
            <a href="{{ $backUrl ?? route('outgoing-letters.show', $letter) }}">Kembali</a>
            <button type="button" onclick="window.print()">Cetak</button>
            @isset($downloadUrl)<a href="{{ $downloadUrl }}">Unduh PDF</a>@endisset
        </div>
    </div>

    @if(count($missingPlaceholders))
        <div class="missing">
            <strong>Placeholder belum terisi:</strong>
            {{ collect($missingPlaceholders)->map(fn ($item) => str_repeat('{', 2).$item.str_repeat('}', 2))->implode(', ') }}
        </div>
    @endif

    @endunless

    <article class="page">
        @if($letter->letterheadProfile)
            <x-official-letterhead :profile="$letter->letterheadProfile" />
        @endif

        <div class="content">{!! $rendered !!}</div>
    </article>

    @if($autoPrint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
