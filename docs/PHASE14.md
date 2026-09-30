# Phase 14 — Arsip Dokumen Surat Terbit

Fokus Phase 14 adalah memastikan surat yang sudah diterbitkan memiliki artefak final yang stabil.

## Fitur
- Snapshot data final saat surat diterbitkan.
- Checksum SHA-256 atas snapshot.
- PDF resmi dibuat otomatis setelah publish.
- PDF disimpan berdasarkan tahun dan ID register.
- Detail Surat Terbit menampilkan status arsip, checksum, dan tombol unduh.
- Surat lama tetap kompatibel walaupun belum memiliki snapshot/PDF Phase 14.

## Struktur arsip
`issued-letters/{tahun}/{issued_letter_id}/surat-{nomor}.pdf`

## Catatan
Phase ini belum menambahkan QR verifikasi publik. QR/verifikasi publik lebih aman dibuat sebagai phase terpisah setelah format arsip final stabil.
