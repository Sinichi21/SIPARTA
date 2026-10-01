#!/usr/bin/env bash
set -Eeuo pipefail

: "${1:?Pass archive path outside repository}"

ARCHIVE="$1"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
ARCHIVE_DIR="$(cd "$(dirname "$ARCHIVE")" && pwd -P)"

[[ "$ARCHIVE_DIR" != "$PROJECT_DIR" && "$ARCHIVE_DIR" != "$PROJECT_DIR/"* ]] || {
  echo "Archive must be outside repository."
  exit 1
}

cd "$PROJECT_DIR"
test -f artisan
test -f vendor/autoload.php
test -f public/build/manifest.json

tar -czf "$ARCHIVE" \
  --exclude='bootstrap/cache/*.php' \
  --exclude='public/hot' \
  --exclude='public/storage' \
  --exclude='*.sqlite' \
  --exclude='*.sqlite-*' \
  app bootstrap config database public resources routes vendor \
  artisan composer.json composer.lock package.json package-lock.json
