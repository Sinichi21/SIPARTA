$ErrorActionPreference = "Stop"

Write-Host "== SIPARTA pre-release check ==" -ForegroundColor Cyan

php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

composer validate --no-check-publish
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

composer types:check
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

php artisan test --compact
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

npm ci --no-audit --no-fund
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

npm run build
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

php artisan route:list --except-vendor
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host ""
Write-Host "[OK] Pre-release check selesai." -ForegroundColor Green
Write-Host "Lanjutkan UAT manual dan deployment checklist sebelum tag v1.0.0." -ForegroundColor Yellow
