# SIPARTA v1.0.0 — Release Checklist

## Automated
- [ ] `php artisan test --compact` hijau
- [ ] static analysis hijau
- [ ] frontend build hijau
- [ ] GitHub CI hijau
- [ ] migration production dry review selesai

## Security
- [ ] `APP_DEBUG=false`
- [ ] HTTPS aktif
- [ ] credential tidak ada di Git
- [ ] role/permission UAT selesai
- [ ] QR verification diuji tanpa login
- [ ] unauthorized direct URL menghasilkan 403
- [ ] backup berhasil dan file backup dapat dibaca

## Documents
- [ ] Surat Masuk upload/download
- [ ] Surat Keluar preview/cetak
- [ ] publish-time numbering
- [ ] SPT → Surat Keluar
- [ ] SPT Preview/Cetak/PDF terbaru
- [ ] Surat Terbit immutable PDF
- [ ] checksum/hash
- [ ] QR verification
- [ ] revoke
- [ ] kop 1/2 gambar

## Reports
- [ ] Rekap SPT
- [ ] Rekap Personil
- [ ] Register Persuratan
- [ ] CSV export

## Operations
- [ ] `/health/ready` = 200
- [ ] log writable
- [ ] storage writable
- [ ] public storage link tersedia
- [ ] DB PostgreSQL terkoneksi
- [ ] cache/database tables tersedia
- [ ] jobs/failed_jobs tersedia
- [ ] backup retention ditentukan
- [ ] restore procedure diuji minimal sekali

## Release
Tag hanya setelah seluruh item wajib selesai:

```bash
git tag -a v1.0.0 -m "SIPARTA v1.0.0"
git push origin v1.0.0
```
