$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Read-Utf8([string]$Path) {
    return [System.IO.File]::ReadAllText((Resolve-Path $Path))
}

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    [System.IO.File]::WriteAllText((Resolve-Path $Path), $Content, $utf8NoBom)
}

function Copy-Source([string]$Relative) {
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
    "app\Models\OutgoingLetter.php",
    "app\Services\OutgoingLetterService.php",
    "app\Services\OutgoingLetterTemplateRenderer.php",
    "app\Livewire\OutgoingLetters\Create.php",
    "app\Livewire\OutgoingLetters\Edit.php",
    "app\Livewire\OutgoingLetters\Show.php",
    "resources\views\livewire\outgoing-letters\form.blade.php",
    "resources\views\livewire\outgoing-letters\show.blade.php"
)

foreach ($file in $required) {
    if (-not (Test-Path $file)) {
        throw "Prasyarat Phase 13.3 tidak ditemukan: $file"
    }
}

$form = Read-Utf8 "resources\views\livewire\outgoing-letters\form.blade.php"

if (
    ($form -notmatch 'correspondence-page') -or
    ($form -notmatch 'correspondence-section')
) {
    throw "Form Surat Keluar lokal tidak cocok dengan UI terbaru. Installer dihentikan agar desain tidak rusak."
}

$backupDir = "storage\app\phase-backups\phase13-3"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

foreach ($file in $required) {
    $safe = $file -replace '[\\/:*?"<>|]', '_'
    Copy-Item $file "$backupDir\$stamp-$safe" -Force
}

$targets = @(
    "app\Models\OutgoingLetter.php",
    "app\Models\OutgoingLetterNumberSequence.php",
    "app\Services\OutgoingLetterNumberService.php",
    "app\Services\OutgoingLetterService.php",
    "app\Services\OutgoingLetterTemplateRenderer.php",
    "app\Livewire\OutgoingLetters\Create.php",
    "app\Livewire\OutgoingLetters\Edit.php",
    "app\Livewire\OutgoingLetters\Show.php",
    "resources\views\livewire\outgoing-letters\form.blade.php",
    "resources\views\livewire\outgoing-letters\show.blade.php",
    "resources\views\livewire\outgoing-letters\partials\a4-preview.blade.php",
    "database\migrations\2026_09_30_133000_add_publish_time_numbering_to_outgoing_letters.php",
    "tests\Feature\PhaseThirteenThreePublishNumberingTest.php",
    "docs\PHASE13-3.md"
)

foreach ($target in $targets) {
    Copy-Source $target
}

Write-Host ""
Write-Host "[OK] Phase 13.3 terpasang." -ForegroundColor Green
Write-Host "Tidak ada perubahan ke app.css, sidebar, header, atau layout." -ForegroundColor Cyan
Write-Host ""
Write-Host "Lanjutkan:" -ForegroundColor Cyan
Write-Host "php artisan migrate"
Write-Host "php artisan view:clear"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact --filter=PhaseThirteenThreePublishNumberingTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
