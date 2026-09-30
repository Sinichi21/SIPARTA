# Phase 12.1 v2 — Sidebar-safe Super-admin Isolation + Personal Staff Portal

Versi ini dibuat setelah sidebar terbaru ditinjau.

## Yang dipertahankan

Installer TIDAK mengganti:
- `resources/css/app.css`
- `resources/views/components/app/nav-link.blade.php`
- `resources/views/components/app/sidebar-group.blade.php`
- `resources/views/components/app/icon.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/components/app/header.blade.php`

Sidebar hanya dipatch minimal:
1. dashboard boleh tampil untuk `dashboard.view` atau `my-dashboard.view`;
2. blok `Administrasi Saya` disisipkan menggunakan class/component yang sudah ada.

## Staff portal

Permission:
- `my-dashboard.view`
- `my-letters.view`
- `my-reports.view`

Role default:
- `staff`

Staff tidak mendapat permission operasional kantor.

SPT personal hanya berasal dari relasi eksplisit `letter_personnel` terhadap `users.personnel_id`.

SPT dengan `personnel_scope=all` tidak dihitung sebagai milik personal agar konsisten dengan aturan historis bahwa ALL PEGAWAI tidak boleh ditempel retroaktif ke setiap personil.

## Super-admin isolation

Untuk non-super-admin:
- super-admin hilang dari daftar Pengguna;
- super-admin hilang dari daftar Role;
- direct URL edit user super-admin ditolak;
- direct URL role super-admin ditolak;
- tindakan security terhadap super-admin ditolak;
- super-admin hilang dari daftar Pemulihan Akun.

Super-admin tetap dikelola oleh developer.
