$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$target = "tests\Feature\PhaseSeventeenCorrespondenceRegisterTest.php"

if (-not (Test-Path $target)) {
    throw "Test Phase 17 tidak ditemukan: $target"
}

$backupDir = "storage\app\phase-backups\phase17-v1-1"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

Copy-Item $target "$backupDir\$stamp-PhaseSeventeenCorrespondenceRegisterTest.php" -Force

$source = Join-Path $PSScriptRoot $target
$content = [System.IO.File]::ReadAllText($source)
[System.IO.File]::WriteAllText((Resolve-Path $target), $content, $utf8NoBom)

Write-Host "[OK] Test fixture Phase 17 diperbaiki." -ForegroundColor Green
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan test --compact --filter=PhaseSeventeenCorrespondenceRegisterTest"
Write-Host "php artisan test --compact"
