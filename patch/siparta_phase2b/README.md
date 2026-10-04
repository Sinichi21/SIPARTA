# SIPARTA Phase 2B — pengajuan SPT

Local-only. Tambah nomor pengajuan internal berbasis ID SPT, tombol Ajukan SPT, dan histori event.
Tidak mengubah nomor surat resmi, arsip terbit, atau DB production.

Ekstrak, dari root SIPARTA jalankan:
`python patch/siparta_phase2b/install_local.py .`
`php artisan migrate`
`php artisan view:clear`
`php artisan test --filter=PhaseTwoBSubmissionTest`
`php artisan test --compact`

Catatan: status Submitted sengaja belum bisa dipublikasikan oleh alur lama. Phase 3 akan menambahkan verifikasi/persetujuan status Submitted sebelum penerbitan. Data impor tidak dapat diajukan ulang. Installer menolak jika struktur kode berbeda.
