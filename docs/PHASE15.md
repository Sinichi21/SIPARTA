# Phase 15 — Verifikasi Publik & QR Surat Terbit

Mengadaptasi pola verifikasi dokumen SIMOPRAM untuk SIPARTA.

## Fitur
- Kode verifikasi publik acak 48 karakter hex.
- QR pada PDF resmi menuju halaman verifikasi publik.
- Halaman verifikasi tidak memerlukan login.
- Metadata publik dibatasi pada data verifikasi surat.
- Counter jumlah pemeriksaan dan waktu pemeriksaan terakhir.
- SHA-256 binary PDF resmi disimpan.
- Download ulang memeriksa hash file sebelum mengirim PDF.
- PDF resmi yang dibuat setelah Phase 15 memuat QR dan kode verifikasi.

## Dependency
Phase 15 menggunakan:

`endroid/qr-code 5.1`

Install sebelum menjalankan installer:

```powershell
composer require endroid/qr-code:5.1
```

## Catatan arsip lama
PDF yang sudah diarsipkan sebelum Phase 15 tidak ditimpa otomatis. Ini disengaja untuk menjaga prinsip immutable archive.
Record lama akan memperoleh kode verifikasi ketika halaman detail dibuka, tetapi PDF lama tidak diregenerasi diam-diam.
