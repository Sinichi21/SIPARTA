$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$target = "app\Livewire\Dashboard.php"
$source = Join-Path $PSScriptRoot "Dashboard.php"

if (-not (Test-Path $target)) {
    throw "Dashboard.php tidak ditemukan."
}

$backupDir = "storage\app\phase-backups"
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

Copy-Item `
    $target `
    "$backupDir\Dashboard-before-phase12-1-v2-4-$stamp.php" `
    -Force

$content = [System.IO.File]::ReadAllText($source)

[System.IO.File]::WriteAllText(
    (Resolve-Path $target),
    $content,
    $utf8NoBom
)

Write-Host "[OK] Dashboard render dependency diperbaiki." -ForegroundColor Green
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php -l app\Livewire\Dashboard.php"
Write-Host "php artisan test --compact --filter=RecapDetailTest"
Write-Host "php artisan test --compact"
