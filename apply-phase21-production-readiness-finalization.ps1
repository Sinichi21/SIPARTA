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
    "app\Http\Controllers\ReadinessController.php",
    "routes\web.php",
    "resources\views\livewire\letters\show.blade.php",
    "app\Http\Controllers\LetterDocumentController.php",
    ".github\workflows\tests.yml"
)

foreach ($file in $required) {
    if (-not (Test-Path $file)) {
        throw "Prasyarat Phase 21 tidak ditemukan: $file"
    }
}

# Protect latest SPT preview/print/download implementation.
$documentController = [System.IO.File]::ReadAllText(
    (Resolve-Path "app\Http\Controllers\LetterDocumentController.php")
)

if (
    ($documentController -notmatch 'linkedDocument') -or
    ($documentController -notmatch 'archivedPdfBinary') -or
    ($documentController -notmatch 'linked-document-preview')
) {
    throw "Flow Detail SPT terbaru tidak terdeteksi. Phase 21 dihentikan agar modifikasi preview/cetak/download tidak tertimpa."
}

$routeContent = [System.IO.File]::ReadAllText(
    (Resolve-Path "routes\web.php")
)

if (
    ($routeContent -notmatch "health\.ready") -or
    ($routeContent -notmatch "throttle:30,1") -or
    ($routeContent -notmatch "throttle:60,1")
) {
    throw "Health/verification hardening Phase 20 belum terdeteksi."
}

$files = @(
    ".env.production.example",
    "scripts\pre-release-check.ps1",
    "scripts\deploy-production.sh",
    "scripts\backup-postgres.sh",
    "scripts\post-deploy-smoke.sh",
    "docs\PHASE21.md",
    "docs\PRODUCTION-DEPLOYMENT.md",
    "docs\MIGRATION-VPS-TO-OFFICE-SERVER.md",
    "docs\RELEASE-V1.0.0.md",
    "tests\Feature\PhaseTwentyOneProductionReadinessTest.php"
)

foreach ($file in $files) {
    Copy-PhaseFile $file
}

Write-Host ""
Write-Host "[OK] Phase 21 Production Readiness & Finalization terpasang." -ForegroundColor Green
Write-Host "Tidak ada migration dan tidak ada business logic/UI existing yang diubah." -ForegroundColor Cyan
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan view:clear"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact --filter=PhaseTwentyOneProductionReadinessTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
Write-Host ""
Write-Host "Opsional pre-release penuh:" -ForegroundColor Cyan
Write-Host ".\scripts\pre-release-check.ps1"
