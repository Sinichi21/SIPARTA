# Phase 15.1 — Pencabutan Dokumen Resmi

Fitur:
- surat terbit dapat dicabut tanpa menghapus arsip;
- alasan pencabutan wajib minimal 10 karakter;
- waktu dan user pencabut dicatat;
- status publik berubah menjadi Dicabut;
- QR/verifikasi publik tetap bekerja dan menampilkan status pencabutan;
- PDF, checksum, nomor surat, dan register historis tetap dipertahankan;
- aksi pencabutan tercatat di audit log;
- permission baru: `issued-letters.revoke`.

Default permission ditambahkan secara additive ke:
- `super-admin`
- `admin-persuratan`
- `pimpinan`

Pencabutan bersifat idempotent: pemanggilan kedua tidak menimpa alasan pencabutan pertama.
