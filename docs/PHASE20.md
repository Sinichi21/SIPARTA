# Phase 20 — Security & UAT Hardening

Baseline yang diperiksa sebelum phase ini:

`7c23a1915d80739a98bdee098c3a8d81c8314b33`

Commit tersebut sudah mencakup modifikasi terbaru pada Detail SPT, preview, cetak,
dan download surat yang terhubung ke Surat Keluar / Surat Terbit. Phase 20 tidak
mengubah tampilan atau alur dokumen tersebut.

## Hardening otomatis

- public verification tetap dapat diakses tanpa login;
- kode verifikasi malformed menghasilkan 404;
- endpoint verifikasi publik diberi rate limit 60 request/menit;
- route persuratan internal tetap wajib auth + verified;
- user tanpa permission ditolak;
- permission `view` tidak otomatis memberi hak `verify`;
- workflow test memastikan denial tidak mengubah status dokumen.

## Checklist UAT role

### super-admin
- seluruh modul dapat dibuka;
- seluruh workflow dapat dijalankan;
- role ini tidak digunakan sebagai akun operasional harian.

### admin-persuratan
- Surat Masuk: view/create/update/process/archive;
- Surat Keluar: view/create/update sesuai kebutuhan operasional;
- Register & laporan;
- revoke hanya bila memang diberikan.

### operator
- input dan koreksi draft;
- tidak boleh verify/approve/publish/revoke.

### verifikator
- melihat dokumen yang relevan;
- verify;
- tidak publish/revoke kecuali diberi permission terpisah.

### pimpinan
- approve/publish;
- revoke bila diberikan;
- tidak perlu mengubah draft operasional.

### viewer
- read-only;
- tidak ada perubahan status.

### staff
- hanya portal personal (SPT Saya/Rekap Saya).

## UAT manual sebelum Phase 21

1. Login masing-masing role.
2. Coba akses URL langsung, bukan hanya menu/sidebar.
3. Pastikan tombol yang tidak berhak tidak tampil.
4. Pastikan URL langsung tetap 403.
5. Uji SPT → Surat Keluar satu kali; buka ulang URL `source_spt` dan pastikan tidak membuat duplikat.
6. Preview/Cetak/PDF pada Detail SPT:
   - sebelum Surat Keluar ada;
   - saat draft Surat Keluar ada;
   - setelah Surat Terbit dibuat;
   - setelah Surat Terbit dicabut.
7. Setelah publish, PDF Detail SPT harus memakai arsip immutable Surat Terbit.
8. Uji QR publik dalam browser tanpa login.
9. Uji kode QR salah/acak.
10. Uji download PDF resmi dan pastikan hash/integritas tidak error.
11. Uji upload file dengan ekstensi yang tidak diizinkan.
12. Uji upload >10 MB.
13. Jalankan full regression test dan build production.

## Catatan role seeder

Phase ini sengaja tidak melakukan `syncPermissions()` ke role operasional.
SIPARTA memiliki UI Role & Permission dan konfigurasi role dapat berubah.
Memaksa matrix lewat seeder berisiko menghapus konfigurasi operasional yang
sudah dibuat administrator. Matrix di atas adalah baseline UAT, sedangkan
permission aktual tetap dikendalikan oleh sistem role/permission.
