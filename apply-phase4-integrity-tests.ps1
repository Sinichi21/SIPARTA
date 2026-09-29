$ErrorActionPreference = "Stop"

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$sourceRoot = Split-Path -Parent $MyInvocation.MyCommand.Path

$files = @(
    "tests\Feature\SptPersonnelScopeIntegrityTest.php",
    "tests\Feature\SptActivityMappingCommandTest.php"
)

foreach ($relative in $files) {
    $source = Join-Path $sourceRoot $relative
    $destination = Join-Path (Get-Location) $relative
    $parent = Split-Path $destination -Parent

    if (-not (Test-Path $source)) {
        throw "File patch tidak ditemukan: $source"
    }

    if (-not (Test-Path $parent)) {
        New-Item -ItemType Directory -Force -Path $parent | Out-Null
    }

    $content = [System.IO.File]::ReadAllText($source)
    [System.IO.File]::WriteAllText($destination, $content, $utf8NoBom)

    Write-Host "[OK] $relative" -ForegroundColor Green
}

Write-Host ""
Write-Host "Phase 4 integrity tests terpasang." -ForegroundColor Cyan
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan test --compact --filter=SptPersonnelScopeIntegrityTest"
Write-Host "php artisan test --compact --filter=SptActivityMappingCommandTest"
Write-Host "php artisan test --compact"
