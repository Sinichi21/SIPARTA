#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/siparta/current}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/siparta}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

set -a
source "$APP_DIR/.env"
set +a

stamp="$(date +'%Y%m%d_%H%M%S')"
target="$BACKUP_DIR/$stamp"
mkdir -p "$target"

PGPASSWORD="${DB_PASSWORD:-}" pg_dump \
  -h "${DB_HOST:-127.0.0.1}" \
  -p "${DB_PORT:-5432}" \
  -U "${DB_USERNAME:?}" \
  -d "${DB_DATABASE:?}" \
  -F c -b --no-owner \
  -f "$target/database.dump"

tar -czf "$target/storage-app.tar.gz" -C "$APP_DIR" storage/app
git -C "$APP_DIR" rev-parse HEAD > "$target/git-commit.txt" 2>/dev/null || true
pg_restore --list "$target/database.dump" >/dev/null

find "$BACKUP_DIR" -mindepth 1 -maxdepth 1 -type d -mtime "+$RETENTION_DAYS" -exec rm -rf {} +

echo "Backup selesai: $target"
