$ErrorActionPreference = "Stop"

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$source = Join-Path $PSScriptRoot "PhaseTwelveOneV2Test.php"
$target = "tests\Feature\PhaseTwelveOneV2Test.php"

if (-not (Test-Path $target)) {
    throw "Test Phase 12.1 tidak ditemukan: $target"
}

$content = [System.IO.File]::ReadAllText($source)

[System.IO.File]::WriteAllText(
    (Resolve-Path $target),
    $content,
    $utf8NoBom
)

Write-Host "[OK] Test Phase 12.1 diperbaiki." -ForegroundColor Green
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan test --compact --filter=PhaseTwelveOneV2Test"
