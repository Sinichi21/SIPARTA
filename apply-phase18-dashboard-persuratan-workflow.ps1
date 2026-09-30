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
    "app\Livewire\Dashboard.php",
    "resources\views\livewire\dashboard.blade.php",
    "app\Models\IncomingLetter.php",
    "app\Models\OutgoingLetter.php",
    "app\Models\IssuedLetter.php"
)

foreach ($file in $required) {
    if (-not (Test-Path $file)) {
        throw "Prasyarat Phase 18 tidak ditemukan: $file"
    }
}

if (-not (Test-Path "app\Livewire\CorrespondenceRegister\Index.php")) {
    throw "Phase 17 belum terdeteksi. Pasang Register Persuratan terlebih dahulu."
}

$backupDir = "storage\app\phase-backups\phase18"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

foreach ($file in @(
    "app\Livewire\Dashboard.php",
    "resources\views\livewire\dashboard.blade.php"
)) {
    $safe = $file -replace '[\\/:*?"<>|]', '_'
    Copy-Item $file "$backupDir\$stamp-$safe" -Force
}

foreach ($file in @(
    "app\Livewire\Dashboard.php",
    "resources\views\livewire\dashboard.blade.php",
    "tests\Feature\PhaseEighteenDashboardWorkflowTest.php",
    "docs\PHASE18.md"
)) {
    Copy-PhaseFile $file
}

Write-Host ""
Write-Host "[OK] Phase 18 berhasil dipasang." -ForegroundColor Green
Write-Host "Tidak ada migration dan tidak mengubah app.css." -ForegroundColor Cyan
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan view:clear"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact --filter=PhaseEighteenDashboardWorkflowTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
