# Deployment Production SIPARTA

## Prasyarat
- Linux server
- PHP 8.3+
- PostgreSQL
- Composer 2
- Node.js 22
- web server Nginx/Apache
- HTTPS
- ekstensi PHP yang dibutuhkan Laravel/PostgreSQL/DOMPDF/QR

## Environment
Gunakan `.env.production.example` sebagai referensi. Jangan commit `.env` aktual.

Nilai penting:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://...`
- `APP_TIMEZONE=Asia/Makassar`
- `DB_CONNECTION=pgsql`
- `SESSION_ENCRYPT=true`
- `LOG_CHANNEL=daily`

Buat key hanya sekali untuk instalasi baru:

```bash
php artisan key:generate
```

Jangan mengganti `APP_KEY` pada instalasi yang sudah aktif tanpa rencana rotasi,
karena data terenkripsi/session dapat terdampak.

## Deployment
Backup database terlebih dahulu.

```bash
export PGDATABASE=siparta
export PGUSER=siparta
./scripts/backup-postgres.sh
```

Kemudian:

```bash
APP_DIR=/var/www/siparta/current ./scripts/deploy-production.sh
```

Script melakukan:
1. maintenance mode;
2. Composer production install;
3. frontend production build;
4. migration `--force`;
5. storage link;
6. permission cache reset;
7. Laravel optimize;
8. keluar maintenance mode.

## Permission direktori
User PHP-FPM/web server harus dapat menulis:
- `storage/`
- `bootstrap/cache/`

Jangan membuat source code seluruh aplikasi writable oleh web server.

## Scheduler / Queue
Saat ini queue default menggunakan database. Bila fitur async digunakan,
jalankan worker melalui systemd/Supervisor:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Setiap deploy, restart worker:

```bash
php artisan queue:restart
```

## Health check
Endpoint:

```text
/health/ready
```

Memeriksa koneksi database dan kemampuan write/delete private storage.

Setelah deploy:

```bash
./scripts/post-deploy-smoke.sh https://domain-siparta
```

## Smoke test browser
- login;
- dashboard;
- Surat Masuk;
- Surat Keluar;
- publish satu dokumen uji bila lingkungan memungkinkan;
- SPT Detail → Preview;
- SPT Detail → Cetak;
- SPT Detail → PDF;
- QR verification tanpa login;
- Register Persuratan;
- role yang tidak berhak harus 403 pada URL langsung.

## Rollback
Rollback source code tidak boleh otomatis melakukan `migrate:rollback`.

Jika deployment baru gagal:
1. aktifkan maintenance mode;
2. kembalikan release/source sebelumnya;
3. jalankan `composer install --no-dev`;
4. build/reuse asset release yang sesuai;
5. `php artisan optimize:clear && php artisan optimize`;
6. verifikasi kompatibilitas schema;
7. keluar maintenance mode.

Jika migration bersifat tidak kompatibel, restore database dari backup terverifikasi.
Jangan mengandalkan rollback migration tanpa backup.
