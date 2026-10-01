# SIPARTA CI/CD — Development & Production

Desain mengikuti pola deployment SIMOPRAM: CI → build → immutable artifact →
SSH upload → release directory → shared `.env`/`storage` → symlink `current`.

## Rule 1 — Development

Trigger:

```text
push ke main
```

atau `workflow_dispatch`.

Flow:

```text
Push main
→ CI
→ Build
→ Package artifact
→ Deploy environment development
```

Development otomatis mengikuti `main`.

## Rule 2 — Production

Production **tidak pernah** deploy karena push biasa.

Trigger:

```text
GitHub Release → Published
```

Syarat:
- stable release;
- bukan draft;
- bukan prerelease;
- tag harus `vX.Y.Z`, contoh `v1.0.0`.

Flow:

```text
Push main
→ development saja

Publish GitHub Release v1.0.0
→ validate release
→ CI
→ checkout tag v1.0.0
→ build immutable artifact
→ deploy environment production
```

## GitHub Environments

Repository → Settings → Environments

Buat:
- `development`
- `production`

Untuk `production`, aktifkan Required reviewers bila tersedia.

## Secrets development

```text
DEV_SSH_HOST
DEV_SSH_PORT
DEV_SSH_USER
DEV_SSH_PRIVATE_KEY
DEV_APP_DIR
```

Contoh APP_DIR:

```text
/var/www/siparta-dev
```

## Secrets production

```text
PROD_SSH_HOST
PROD_SSH_PORT
PROD_SSH_USER
PROD_SSH_PRIVATE_KEY
PROD_APP_DIR
```

Contoh APP_DIR:

```text
/var/www/siparta
```

## Struktur server

```text
/var/www/siparta[-dev]/
├── current -> releases/<release>
├── releases/
└── shared/
    ├── .env
    └── storage/
```

Nginx/Apache harus menunjuk ke:

```text
.../current/public
```

## Setup awal server

Development:

```bash
sudo mkdir -p /var/www/siparta-dev/{releases,shared}
sudo chown -R siparta-deploy:www-data /var/www/siparta-dev
sudo chmod -R 2775 /var/www/siparta-dev
```

Production:

```bash
sudo mkdir -p /var/www/siparta/{releases,shared}
sudo chown -R siparta-deploy:www-data /var/www/siparta
sudo chmod -R 2775 /var/www/siparta
```

Buat `.env` masing-masing:

```bash
nano /var/www/siparta-dev/shared/.env
nano /var/www/siparta/shared/.env
```

Development dan production wajib database berbeda.

## Deploy development

Cukup:

```bash
git push origin main
```

Workflow:

```text
SIPARTA Development Deploy
```

akan jalan otomatis.

## Deploy production

Setelah UAT:

```bash
git tag -a v1.0.0 -m "SIPARTA v1.0.0"
git push origin v1.0.0
```

Lalu GitHub:

```text
Releases
→ Draft a new release
→ pilih v1.0.0
→ Publish release
```

Baru setelah **Publish release** production berjalan.

Push `main` tidak memicu production.

## Rollback

Deployment source bersifat atomic menggunakan symlink `current`.
Jika deploy gagal setelah aktivasi, script mengembalikan source release sebelumnya.

Migration database tidak di-rollback otomatis. Backup database tetap wajib sebelum
release production yang membawa migration penting.
