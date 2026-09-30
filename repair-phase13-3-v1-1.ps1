$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Copy-Repair([string]$Relative) {
    $source = Join-Path $PSScriptRoot $Relative
    $target = Join-Path (Get-Location) $Relative
    $parent = Split-Path $target -Parent

    if (-not (Test-Path $target)) {
        throw "Target tidak ditemukan: $Relative"
    }

    $content = [System.IO.File]::ReadAllText($source)
    [System.IO.File]::WriteAllText((Resolve-Path $target), $content, $utf8NoBom)

    Write-Host "[OK] $Relative" -ForegroundColor Green
}

$backupDir = "storage\app\phase-backups\phase13-3-v1-1"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

$targets = @(
    "app\Services\OutgoingLetterService.php",
    "resources\views\livewire\outgoing-letters\partials\a4-preview.blade.php",
    "resources\views\livewire\outgoing-letters\form.blade.php"
)

foreach ($file in $targets) {
    if (-not (Test-Path $file)) {
        throw "Prasyarat tidak ditemukan: $file"
    }

    $safe = $file -replace '[\\/:*?"<>|]', '_'
    Copy-Item $file "$backupDir\$stamp-$safe" -Force
}

foreach ($file in $targets) {
    Copy-Repair $file
}

Write-Host ""
Write-Host "[OK] Phase 13.3 v1.1 repair selesai." -ForegroundColor Green
Write-Host ""
Write-Host "Perbaikan:" -ForegroundColor Cyan
Write-Host "- Blade ParseError pada A4 preview"
Write-Host "- Hint placeholder pada form dibuat parser-safe"
Write-Host "- OutgoingLetterService::number() dipulihkan sebagai compatibility shim"
Write-Host "- number() TIDAK mengalokasikan nomor final; finalisasi tetap saat publish"
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php -l app\Services\OutgoingLetterService.php"
Write-Host "php artisan view:clear"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact --filter=PhaseThirteenCorrespondenceTest"
Write-Host "php artisan test --compact --filter=PhaseThirteenThreePublishNumberingTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
