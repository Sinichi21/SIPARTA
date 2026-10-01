#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/siparta/current}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

cd "$APP_DIR"

if [[ ! -f artisan || ! -f composer.json || ! -f package.json ]]; then
  echo "[ERROR] APP_DIR bukan root SIPARTA: $APP_DIR" >&2
  exit 1
fi

if [[ ! -f .env ]]; then
  echo "[ERROR] File .env production belum tersedia." >&2
  exit 1
fi

echo "== SIPARTA production deploy =="
echo "Directory: $APP_DIR"

"$PHP_BIN" artisan down --retry=60 || true

cleanup() {
  "$PHP_BIN" artisan up || true
}
trap cleanup EXIT

"$COMPOSER_BIN" install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --no-progress \
  --optimize-autoloader

"$NPM_BIN" ci --no-audit --no-fund
"$NPM_BIN" run build

"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link || true
"$PHP_BIN" artisan permission:cache-reset || true

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

"$PHP_BIN" artisan up
trap - EXIT

echo "[OK] Deployment selesai."
echo "Verifikasi: /health/ready, login, Surat Masuk, Surat Keluar, SPT, PDF, QR."
