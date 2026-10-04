<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
@page { size: A4 portrait; margin: 18mm 15mm 24mm; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color:#161616; }
.heading { text-align: right; margin-bottom: 10mm; font-size: 10pt; }
.people { width:100%; border-collapse:collapse; table-layout:fixed; }
.people th,.people td { border:1px solid #222; padding:5px 4px; vertical-align:top; overflow-wrap:break-word; }
.people thead { display: table-header-group; }
.people tr { page-break-inside:avoid; }
.people th { text-align:center; }
.verify { position:fixed; bottom:-16mm; left:0; right:0; border-top:.5px solid #777; padding-top:2mm; font-size:7pt; color:#555; }
.verify img { width:14mm; height:14mm; vertical-align:middle; }
</style>
</head>
<body>
<div class="heading">Lampiran Surat Tugas Kolektif<br>Nomor: {{ $issued->number }}<br>Tanggal: {{ $issued->letter_date?->translatedFormat('d F Y') }}</div>
<table class="people">
<thead><tr><th style="width:5%">No</th><th style="width:26%">Nama</th><th style="width:25%">NIP</th><th style="width:18%">Pangkat/Gol./Ruang</th><th style="width:26%">Jabatan</th></tr></thead>
<tbody>
@foreach($people as $person)
<tr>
<td style="text-align:center">{{ $loop->iteration }}</td>
<td>{{ $person['name'] }}</td>
<td>{{ $person['nip'] ?: '-' }}</td>
<td>{{ implode(' / ', array_filter([$person['rank'], $person['grade']])) ?: '-' }}</td>
<td>{{ $person['position'] ?: '-' }}</td>
</tr>
@endforeach
</tbody></table>
<div class="verify">@if($qrDataUri)<img src="{{ $qrDataUri }}" alt="">@endif
Nomor dan QR verifikasi sama dengan surat utama: {{ $issued->number }} — {{ $verificationUrl }}</div>
</body></html>
