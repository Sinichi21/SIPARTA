# SIPARTA Phase 3A — Pemeriksaan Pengajuan SPT

Patch lokal untuk instalasi **setelah Phase 2B**. Tidak mengubah GitHub maupun database production.

Fitur: verifikasi pengajuan, persetujuan setelah verifikasi, pengembalian ke draft untuk revisi dengan alasan wajib, riwayat tindakan pada tabel `spt_submission_events` yang sudah ditambahkan Phase 2B. Memakai izin `letters.verify` dan `letters.approve` yang sudah terdapat di aplikasi. Pengajuan lama/import dan surat terbit tidak terpengaruh. Nomor pengajuan dipertahankan ketika revisi dan pengajuan ulang.

**Batas fase:** Transisi disetujui → penerbitan resmi akan diintegrasikan pada Phase 3B. Tidak ada pembangkitan nomor resmi pada Phase 3A. Jalur `letters.publish` untuk draft pengujian lama belum diubah.

## Pemasangan

Ekstrak arsip lalu jalankan dari root proyek:

```powershell
python ".\patch\siparta_phase3a\install_local.py" .
php artisan view:clear
php artisan test --filter=PhaseThreeAReviewTest
php artisan test --compact
```

Tidak diperlukan migrasi. Installer menolak pemasangan ganda atau instalasi tanpa Phase 2B, serta mencadangkan file yang akan diubah.

**Tes manual:** login pengguna yang memiliki `letters.verify` lalu verifikasi pengajuan baru; login pengguna dengan `letters.approve` untuk menyetujui, atau kembalikan untuk revisi dengan catatan. Jangan gunakan data resmi untuk pengujian.
