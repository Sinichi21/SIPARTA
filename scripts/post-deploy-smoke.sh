#!/usr/bin/env bash
set -Eeuo pipefail

BASE_URL="${1:-${APP_URL:-}}"

if [[ -z "$BASE_URL" ]]; then
  echo "Usage: $0 https://siparta.example.go.id" >&2
  exit 1
fi

BASE_URL="${BASE_URL%/}"

echo "Checking $BASE_URL/health/ready"
curl --fail --silent --show-error \
  --max-time 15 \
  "$BASE_URL/health/ready"

echo
echo "[OK] Readiness endpoint merespons sukses."
echo "Lanjutkan smoke test login dan workflow melalui browser."
