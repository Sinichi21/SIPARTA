$ErrorActionPreference = "Stop"

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$source = Join-Path $PSScriptRoot "tests\Unit\SptImportPersonnelScopeTest.php"
$destination = Join-Path (Get-Location) "tests\Unit\SptImportPersonnelScopeTest.php"

if (-not (Test-Path $source)) {
    throw "File patch tidak ditemukan: $source"
}

$content = [System.IO.File]::ReadAllText($source)
[System.IO.File]::WriteAllText($destination, $content, $utf8NoBom)

Write-Host "[OK] SptImportPersonnelScopeTest.php dikonversi ke PHPUnit murni." -ForegroundColor Green
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php -l tests\Unit\SptImportPersonnelScopeTest.php"
Write-Host "php artisan test --compact --filter=SptImportPersonnelScopeTest"
Write-Host "php artisan test --compact --filter=SptPersonnelScopeIntegrityTest"
Write-Host "php artisan test --compact --filter=SptActivityMappingCommandTest"
Write-Host "php artisan test --compact"
