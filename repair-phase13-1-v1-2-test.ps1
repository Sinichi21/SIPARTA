$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$source = Join-Path $PSScriptRoot "PhaseThirteenOneIncomingTemplateTest.php"
$target = "tests\Feature\PhaseThirteenOneIncomingTemplateTest.php"

if (-not (Test-Path $target)) {
    throw "Test Phase 13.1 tidak ditemukan: $target"
}

$content = [System.IO.File]::ReadAllText($source)

[System.IO.File]::WriteAllText(
    (Resolve-Path $target),
    $content,
    $utf8NoBom
)

Write-Host "[OK] Test fixture Phase 13.1 diperbaiki." -ForegroundColor Green
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan test --compact --filter=PhaseThirteenOneIncomingTemplateTest"
