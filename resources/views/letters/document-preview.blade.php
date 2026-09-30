<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview {{ $payload['letter']['number'] ?: 'Draft SPT' }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm; }
        * { box-sizing: border-box; }
        body { margin:0; background:#eef2f7; color:#111827; font-family:"Times New Roman", Times, serif; }
        .document-toolbar { position:sticky; top:0; z-index:20; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 18px; background:#0f2f57; color:#fff; font-family:Arial,sans-serif; box-shadow:0 2px 10px rgba(15,23,42,.15); }
        .document-toolbar-info strong { display:block; font-size:13px; }
        .document-toolbar-info span { display:block; margin-top:3px; color:#bdd6f4; font-size:11px; }
        .document-toolbar-actions { display:flex; flex-wrap:wrap; gap:8px; }
        .document-toolbar a,.document-toolbar button { display:inline-flex; align-items:center; justify-content:center; border:1px solid #ffffff35; border-radius:7px; padding:8px 11px; background:#ffffff10; color:#fff; font-size:12px; font-weight:600; text-decoration:none; cursor:pointer; }
        .snapshot-note { width:210mm; margin:18px auto -8px; border:1px solid #d8e4f2; border-radius:8px; padding:10px 12px; background:#f8fbff; color:#526987; font-family:Arial,sans-serif; font-size:11px; }
        .document-page { width:210mm; min-height:297mm; margin:24px auto; padding:18mm; background:#fff; box-shadow:0 8px 30px rgba(15,23,42,.12); }
        .letterhead { border-bottom:4px double #111; padding-bottom:8px; margin-bottom:24px; }
        .letterhead-grid { display:grid; grid-template-columns:90px 1fr 90px; align-items:center; gap:12px; }
        .letterhead-logo { text-align:center; }
        .letterhead-logo img { max-width:76px; max-height:76px; }
        .letterhead-center { text-align:center; }
        .parent-org { font-size:13px; font-weight:700; text-transform:uppercase; }
        .organization { font-size:18px; line-height:1.15; font-weight:700; text-transform:uppercase; }
        .contact { margin-top:3px; font-size:10px; line-height:1.25; }
        .document-content { font-size:12pt; line-height:1.5; }
        .document-content p { margin:0 0 8px; }
        .document-content table { width:100%; border-collapse:collapse; margin:8px 0; }
        .document-content table td,.document-content table th { border:1px solid #111; padding:5px; vertical-align:top; }
        .document-content ul,.document-content ol { margin-top:4px; margin-bottom:8px; }
        .signature { width:280px; margin-top:42px; margin-left:auto; font-size:12pt; line-height:1.4; }
        .signature p { margin:0; }
        .signature-space { height:72px; }
        .signatory-name { font-weight:700; text-decoration:underline; }
        @media print {
            body { background:#fff; }
            .document-toolbar,.snapshot-note { display:none !important; }
            .document-page { width:auto; min-height:auto; margin:0; padding:0; box-shadow:none; }
        }
        @media (max-width:900px) {
            .document-page,.snapshot-note { width:calc(100% - 24px); }
            .document-page { padding:28px; }
        }
    </style>
</head>
<body>
    <div class="document-toolbar">
        <div class="document-toolbar-info">
            <strong>{{ $payload['letter']['number'] ?: 'Draft SPT #'.$letter->id }}</strong>
            <span>
                {{ $payload['is_snapshot'] ? 'Snapshot dokumen terkunci' : 'Preview dinamis draft' }}
                · Template {{ $payload['template']['name'] ?? '-' }}
                @if($payload['template']['version'] ?? null) v{{ $payload['template']['version'] }} @endif
            </span>
        </div>
        <div class="document-toolbar-actions">
            <a href="{{ route('letters.show', $letter) }}">Kembali</a>
            <a href="{{ route('letters.document.pdf', $letter) }}">PDF</a>
            <button type="button" onclick="window.print()">Cetak</button>
        </div>
    </div>

    @if($payload['is_snapshot'])
        <div class="snapshot-note">
            Dokumen ini menggunakan snapshot yang dikunci
            {{ $payload['generated_at'] ? 'pada '.$payload['generated_at']->translatedFormat('d F Y H:i') : '' }}.
            Perubahan template atau kop setelah snapshot dibuat tidak mengubah dokumen ini.
            @if($payload['checksum_sha256']) Checksum: {{ $payload['checksum_sha256'] }} @endif
        </div>
    @endif

    <article class="document-page">
        @include('letters.document-body')
    </article>

    @if($autoPrint)
        <script>window.addEventListener('load',()=>window.print());</script>
    @endif
</body>
</html>
