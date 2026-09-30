# Phase 15.2 — Konsistensi Kop Surat

Masalah:
- preview pada Pengaturan Kop memakai `x-letterhead-preview`;
- preview/cetak SPT memiliki markup kop sendiri;
- preview Surat Keluar memiliki markup kop sendiri;
- PDF Surat Terbit memiliki markup kop sendiri.

Akibatnya desain yang sama menghasilkan garis, logo, ukuran, dan spacing yang berbeda.

## Perbaikan
Satu sumber render baru:

`resources/views/components/official-letterhead.blade.php`

Komponen yang sama sekarang dipakai oleh:
- Pengaturan Kop;
- preview Template;
- preview/cetak/PDF SPT;
- preview A4 Surat Keluar;
- preview standalone Surat Keluar bila route lama masih digunakan;
- PDF resmi Surat Terbit.

## Print safety
Kop menggunakan:
- table layout sederhana;
- inline CSS;
- `3px double` untuk garis;
- logo dalam data URI/base64 dari storage;
- ukuran logo dan tipografi konsisten.

Pendekatan ini menghindari ketergantungan terhadap Tailwind/flex/grid ketika DOMPDF merender dokumen.

## Catatan
Business logic surat, numbering, workflow, QR, dan revoke tidak diubah.
