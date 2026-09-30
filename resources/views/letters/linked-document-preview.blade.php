<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat SPT {{ $issued->number }}</title>
    <style>
        body { margin: 0; background: #eef2f7; font: 14px/1.5 Arial, sans-serif; }
        header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 16px; background: #10365f; color: white; }
        nav { display: flex; flex-wrap: wrap; gap: 8px; }
        a, button { padding: 8px 12px; border: 1px solid #ffffff55; border-radius: 6px; color: white; background: transparent; text-decoration: none; font: inherit; cursor: pointer; }
        p { margin: 4px 0 0; font-size: 12px; }
        .notice { padding: 12px 16px; background: #fff4d7; color: #805f17; }
        iframe { display: block; width: 100%; height: calc(100vh - 130px); border: 0; }
    </style>
</head>
<body>
    <header>
        <div><strong>{{ $issued->number }}</strong><p>Arsip PDF resmi Surat Terbit</p></div>
        <nav aria-label="Aksi dokumen">
            <a href="{{ route('letters.show', $letter) }}">Kembali ke SPT</a>
            <button type="button" onclick="printDocument()">Cetak</button>
            <a href="{{ route('letters.document.pdf', $letter) }}">Unduh PDF</a>
        </nav>
    </header>
    @if($issued->isRevoked())
        <div class="notice" role="alert">Surat ini telah dicabut. Arsip dipertahankan untuk riwayat.</div>
    @endif
    <p class="notice">Jika preview atau cetak tidak tersedia di browser, unduh PDF lalu buka dan cetak dokumen tersebut.</p>
    <iframe id="document-pdf" title="Arsip PDF surat SPT" src="{{ route('letters.document.pdf', ['letter' => $letter, 'inline' => 1]) }}"></iframe>
    <script>
        function printDocument() {
            const frame = document.getElementById('document-pdf');
            try { frame.contentWindow.focus(); frame.contentWindow.print(); }
            catch (error) { window.open(frame.src, '_blank', 'noopener'); }
        }
        @if($autoPrint)
            document.getElementById('document-pdf').addEventListener('load', printDocument, { once: true });
        @endif
    </script>
</body>
</html>
