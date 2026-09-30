$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Copy-PhaseFile([string]$Relative) {
    $source = Join-Path $PSScriptRoot $Relative
    $target = Join-Path (Get-Location) $Relative
    $parent = Split-Path $target -Parent

    if (-not (Test-Path $parent)) {
        New-Item -ItemType Directory -Force -Path $parent | Out-Null
    }

    $content = [System.IO.File]::ReadAllText($source)

    if (Test-Path $target) {
        [System.IO.File]::WriteAllText((Resolve-Path $target), $content, $utf8NoBom)
    } else {
        [System.IO.File]::WriteAllText($target, $content, $utf8NoBom)
    }

    Write-Host "[OK] $Relative" -ForegroundColor Green
}

$required = @(
    "app\Services\OutgoingLetterService.php",
    "app\Services\OutgoingLetterTemplateRenderer.php",
    "app\Services\OutgoingLetterNumberService.php",
    "app\Models\OutgoingLetter.php",
    "app\Models\IssuedLetter.php",
    "app\Livewire\IssuedLetters\Show.php",
    "resources\views\livewire\issued-letters\show.blade.php"
)

foreach ($file in $required) {
    if (-not (Test-Path $file)) {
        throw "Prasyarat tidak ditemukan: $file. Pastikan Phase 13.3 v1.1 sudah terpasang."
    }
}

$serviceContent = [System.IO.File]::ReadAllText(
    (Resolve-Path "app\Services\OutgoingLetterService.php")
)

if (
    ($serviceContent -notmatch 'OutgoingLetterNumberService') -or
    ($serviceContent -notmatch 'numbering_mode')
) {
    throw "OutgoingLetterService lokal belum menggunakan business logic Phase 13.3. Installer dihentikan."
}

$backupDir = "storage\app\phase-backups\phase14"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

foreach ($file in @(
    "app\Services\OutgoingLetterService.php",
    "app\Models\IssuedLetter.php",
    "app\Livewire\IssuedLetters\Show.php",
    "resources\views\livewire\issued-letters\show.blade.php"
)) {
    $safe = $file -replace '[\\/:*?"<>|]', '_'
    Copy-Item $file "$backupDir\$stamp-$safe" -Force
}

$targets = @(
    "app\Models\IssuedLetter.php",
    "app\Services\IssuedLetterArchiveService.php",
    "app\Services\OutgoingLetterService.php",
    "app\Livewire\IssuedLetters\Show.php",
    "database\migrations\2026_09_30_140000_add_archive_fields_to_issued_letters.php",
    "resources\views\issued-letters\pdf.blade.php",
    "resources\views\livewire\issued-letters\show.blade.php",
    "tests\Feature\PhaseFourteenIssuedArchiveTest.php",
    "docs\PHASE14.md"
)

foreach ($file in $targets) {
    Copy-PhaseFile $file
}

Write-Host ""
Write-Host "[OK] Phase 14 berhasil dipasang." -ForegroundColor Green
Write-Host "Tidak mengubah app.css, sidebar, header, atau layout utama." -ForegroundColor Cyan
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan migrate"
Write-Host "php artisan view:clear"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact --filter=PhaseFourteenIssuedArchiveTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
