#!/usr/bin/env bash
set -Eeuo pipefail

: "${PGHOST:=127.0.0.1}"
: "${PGPORT:=5432}"
: "${PGDATABASE:?Set PGDATABASE}"
: "${PGUSER:?Set PGUSER}"
: "${BACKUP_DIR:=/var/backups/siparta}"

mkdir -p "$BACKUP_DIR"

STAMP="$(date +%Y%m%d-%H%M%S)"
OUT="$BACKUP_DIR/siparta-$STAMP.dump"

pg_dump \
  --host="$PGHOST" \
  --port="$PGPORT" \
  --username="$PGUSER" \
  --format=custom \
  --blobs \
  --no-owner \
  --verbose \
  --file="$OUT" \
  "$PGDATABASE"

echo "[OK] Backup PostgreSQL: $OUT"
