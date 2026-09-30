# Phase 13.3 — Publish-time Numbering & Live Print Preview

Business logic diadaptasi dari SIMOPRAM, sedangkan gaya UI tetap SIPARTA.

## Prinsip
- Draft tidak mendapat nomor final.
- Verifikasi dan persetujuan tidak menghabiskan sequence.
- Nomor otomatis dialokasikan di dalam transaksi saat Publish.
- Sequence dikunci dengan `lockForUpdate()`.
- Nomor manual diperiksa saat input dan diperiksa ulang saat Publish.
- Tanggal final juga ditetapkan saat Publish.
- Detail surat menampilkan preview A4 langsung di bawah detail.
- Form memiliki toggle Preview Cetak.
- Preview draft memakai placeholder seperti `(nomor otomatis saat terbit)` dan tidak mengubah sequence.

## Format nomor otomatis
Menggunakan `letter_types.numbering_pattern`.

Token:
- `{sequence}`
- `{sequence_padded}`
- `{type}`
- `{month}`
- `{month_roman}`
- `{year}`
- `{year_short}`

Default:
`{sequence_padded}/{type}/{month_roman}/{year}`

## Catatan
State `Numbered` tetap didukung untuk kompatibilitas data lama, tetapi flow baru normal adalah:
Draft → Verified → Approved → Published.
