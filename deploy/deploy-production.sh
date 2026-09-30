#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/siparta/current}"
cd "$APP_DIR"

php artisan down --retry=60 || true
trap 'echo "Deploy gagal; maintenance mode tetap aktif."' ERR

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress
npm ci --no-audit --no-fund
npm run build
php artisan migrate --force
php artisan storage:link || true
php artisan optimize:clear
php artisan optimize
php artisan queue:restart || true
php artisan app:production-check

trap - ERR
php artisan up
echo "Deploy selesai."
