# Migrasi SIPARTA dari VPS Sementara ke Server Kantor

## Yang harus dipindahkan
1. source/release SIPARTA;
2. `.env` production (buat ulang secara aman, jangan melalui Git);
3. database PostgreSQL;
4. `storage/app/private`;
5. `storage/app/public`;
6. key aplikasi yang sama (`APP_KEY`) bila memindahkan instalasi aktif;
7. konfigurasi web server dan TLS yang baru.

## Urutan migrasi
1. siapkan server kantor dan dependency;
2. deploy source versi yang sama dengan VPS;
3. maintenance mode pada VPS;
4. backup PostgreSQL final;
5. sinkronkan storage;
6. restore database di server kantor;
7. pasang `.env` server kantor;
8. pastikan `APP_KEY` sesuai instalasi yang dipindahkan;
9. `php artisan migrate --force`;
10. `php artisan storage:link`;
11. `php artisan optimize`;
12. jalankan `/health/ready`;
13. UAT singkat;
14. alihkan DNS/reverse proxy;
15. pantau log;
16. pertahankan VPS lama dalam keadaan read-only/offline sementara sampai migrasi tervalidasi.

## Database restore contoh

```bash
createdb -U postgres siparta
pg_restore \
  --clean \
  --if-exists \
  --no-owner \
  --dbname=siparta \
  /path/to/siparta-final.dump
```

Sesuaikan user/permission PostgreSQL dengan kebijakan server kantor.

## Validasi file
Karena SIPARTA menyimpan PDF resmi dan lampiran, hitung ukuran/jumlah file sebelum
dan sesudah migrasi. PDF Surat Terbit juga memiliki hash aplikasi; buka beberapa
dokumen sampling untuk memastikan verifikasi integritas tetap lolos.

## Cutover
Hindari dua instance writable terhadap database/storage berbeda pada waktu yang sama.
Saat cutover, satu instance harus menjadi sumber penulisan tunggal.
