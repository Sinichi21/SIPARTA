<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $issued->number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111;
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.45;
        }

        .letterhead {
            border-bottom: 1.6px solid #111;
            margin-bottom: 5mm;
            padding-bottom: 3mm;
            text-align: center;
        }

        .letterhead-parent {
            font-size: 12pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .letterhead-name {
            font-size: 15pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .letterhead-address {
            margin-top: 1mm;
            font-size: 9.5pt;
        }

        .content {
            font-size: 12pt;
            line-height: 1.45;
        }

        .content p,
        .content div {
            margin: 0 0 2.5mm;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0 3mm;
        }

        .content td,
        .content th {
            padding: 1.3mm 1.8mm;
            vertical-align: top;
        }

        .content td:not([style]),
        .content th:not([style]) {
            border: .6px solid #555;
        }

        .integrity {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -11mm;
            border-top: .6px solid #999;
            padding-top: 2mm;
            color: #555;
            font-family: Arial, sans-serif;
            font-size: 7.5pt;
            line-height: 1.25;
        }

        .integrity table {
            width: 100%;
            border-collapse: collapse;
        }

        .integrity td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }

        .integrity .qr {
            width: 22mm;
        }

        .integrity .qr img {
            width: 18mm;
            height: 18mm;
        }

        .integrity strong {
            color: #222;
        }
    </style>
</head>
<body>
    @if($letter->letterheadProfile)
        <x-official-letterhead :profile="$letter->letterheadProfile" />
    @endif

    <div class="content">{!! $rendered !!}</div>

    <footer class="integrity">
        <table>
            <tr>
                <td class="qr">
                    @if($qrDataUri)
                        <img src="{{ $qrDataUri }}" alt="QR Verifikasi">
                    @endif
                </td>
                <td>
                    <strong>Verifikasi Dokumen SIPARTA</strong><br>
                    Scan QR untuk memeriksa status dokumen resmi.<br>
                    Nomor: {{ $issued->number }}<br>
                    Kode: {{ $issued->verification_code }}<br>
                    SHA-256 snapshot: {{ $checksum }}
                </td>
            </tr>
        </table>
    </footer>
</body>
</html>
