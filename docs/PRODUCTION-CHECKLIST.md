# SIPARTA Production Checklist

## Environment
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://...`
- `APP_TIMEZONE=Asia/Makassar`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_ENCRYPT=true`
- PostgreSQL production terpisah dari database development/test
- jangan commit `.env`

## PHP
Pastikan extension penting aktif:

```bash
php -m | grep -E 'pdo_pgsql|pgsql|mbstring|openssl|fileinfo'
```

## File permissions

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Jangan gunakan `777` untuk seluruh project.

## Deploy

Sebelum migrate, buat backup:

```bash
bash deploy/backup-production.sh
```

Kemudian deploy:

```bash
bash deploy/deploy-production.sh
```

Jangan gunakan `migrate:fresh`, `db:wipe`, atau reset destruktif di production.

## Health
- `/up` = liveness Laravel
- `/health/ready` = database + private storage readiness
- `php artisan app:production-check` = validasi sebelum go-live

## Backup
Backup minimal:
- PostgreSQL custom dump
- `storage/app`
- commit Git yang sedang aktif

Verifikasi dump dengan:

```bash
pg_restore --list database.dump
```

Simpan setidaknya satu salinan di mesin/lokasi berbeda.

## Restore drill
Lakukan pada database test, bukan production:

```bash
createdb -O persuratan siparta_restore_test
pg_restore --no-owner -d siparta_restore_test /path/database.dump
```

## Smoke test go-live
Cek:
- login/logout
- dashboard
- create/edit draft SPT
- attachment
- publish
- snapshot dokumen
- preview/PDF/print
- rekap SPT/personil
- template
- kop & administrasi
- audit log
- role/permission

## Migrasi VPS sementara ke server kantor
1. maintenance mode server lama
2. backup database + storage
3. verifikasi backup
4. deploy commit yang sama di server baru
5. konfigurasi `.env`
6. restore database
7. restore `storage/app`
8. install dependency/build
9. migrate
10. storage link
11. optimize
12. production check
13. smoke test
14. alihkan DNS/reverse proxy
15. pantau log
16. jangan langsung hapus server lama
