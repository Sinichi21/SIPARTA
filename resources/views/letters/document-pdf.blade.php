<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size:A4 portrait; margin:18mm; }
        * { box-sizing:border-box; }
        body { margin:0; color:#111827; font-family:"Times New Roman", Times, serif; }
        .document-page { width:100%; }
        .letterhead { border-bottom:4px double #111; padding-bottom:8px; margin-bottom:24px; }
        .letterhead-grid { display:table; width:100%; table-layout:fixed; }
        .letterhead-logo,.letterhead-center,.letterhead-grid>div:last-child { display:table-cell; vertical-align:middle; }
        .letterhead-logo { width:90px; text-align:center; }
        .letterhead-grid>div:last-child { width:90px; }
        .letterhead-logo img { max-width:76px; max-height:76px; }
        .letterhead-center { text-align:center; }
        .parent-org { font-size:13px; font-weight:700; text-transform:uppercase; }
        .organization { font-size:18px; line-height:1.15; font-weight:700; text-transform:uppercase; }
        .contact { margin-top:3px; font-size:10px; line-height:1.25; }
        .document-content { font-size:12pt; line-height:1.5; }
        .document-content p { margin:0 0 8px; }
        .document-content table { width:100%; border-collapse:collapse; margin:8px 0; }
        .document-content table td,.document-content table th { border:1px solid #111; padding:5px; vertical-align:top; }
        .signature { width:280px; margin-top:42px; margin-left:auto; font-size:12pt; line-height:1.4; }
        .signature p { margin:0; }
        .signature-space { height:72px; }
        .signatory-name { font-weight:700; text-decoration:underline; }
    </style>
</head>
<body>
    <article class="document-page">
        @include('letters.document-body')
    </article>
</body>
</html>
