$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Read-Utf8([string]$Path) {
    return [System.IO.File]::ReadAllText((Resolve-Path $Path))
}

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    [System.IO.File]::WriteAllText((Resolve-Path $Path), $Content, $utf8NoBom)
}

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

& php -r "require 'vendor/autoload.php'; exit(class_exists('Endroid\\QrCode\\Builder\\Builder') ? 0 : 1);"

if ($LASTEXITCODE -ne 0) {
    throw "Dependency endroid/qr-code belum terpasang. Jalankan: composer require endroid/qr-code:5.1"
}

$required = @(
    "app\Models\IssuedLetter.php",
    "app\Services\IssuedLetterArchiveService.php",
    "app\Services\OutgoingLetterService.php",
    "app\Livewire\IssuedLetters\Show.php",
    "resources\views\issued-letters\pdf.blade.php",
    "resources\views\livewire\issued-letters\show.blade.php",
    "routes\web.php"
)

foreach ($file in $required) {
    if (-not (Test-Path $file)) {
        throw "Prasyarat Phase 15 tidak ditemukan: $file. Pastikan Phase 14 sudah terpasang."
    }
}

$issuedModel = Read-Utf8 "app\Models\IssuedLetter.php"

if (
    ($issuedModel -notmatch 'snapshot_json') -or
    ($issuedModel -notmatch 'checksum_sha256')
) {
    throw "Model IssuedLetter belum menggunakan Phase 14. Installer dihentikan."
}

$backupDir = "storage\app\phase-backups\phase15"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

foreach ($file in $required) {
    $safe = $file -replace '[\\/:*?"<>|]', '_'
    Copy-Item $file "$backupDir\$stamp-$safe" -Force
}

$targets = @(
    "app\Models\IssuedLetter.php",
    "app\Services\IssuedLetterVerificationService.php",
    "app\Services\IssuedLetterArchiveService.php",
    "app\Http\Controllers\IssuedLetterVerificationController.php",
    "app\Livewire\IssuedLetters\Show.php",
    "database\migrations\2026_09_30_150000_add_public_verification_to_issued_letters.php",
    "resources\views\issued-letters\pdf.blade.php",
    "resources\views\verification\issued-letter.blade.php",
    "resources\views\livewire\issued-letters\show.blade.php",
    "tests\Feature\PhaseFifteenPublicVerificationTest.php",
    "docs\PHASE15.md"
)

foreach ($file in $targets) {
    Copy-PhaseFile $file
}

# Patch public route outside the auth middleware group.
$routePath = "routes\web.php"
$content = Read-Utf8 $routePath

$controllerImport = "use App\Http\Controllers\IssuedLetterVerificationController;"

if (-not $content.Contains($controllerImport)) {
    $anchor = "use App\Http\Controllers\LetterDocumentController;"

    if (-not $content.Contains($anchor)) {
        throw "Anchor import LetterDocumentController tidak ditemukan."
    }

    $content = $content.Replace(
        $anchor,
        ($controllerImport + "`r`n" + $anchor)
    )
}

if ($content -notmatch "issued-letters\.verify") {
    $anchor = @'
Route::get('/', function () {
'@

    if (-not $content.Contains($anchor)) {
        throw "Anchor route home tidak ditemukan."
    }

    $publicRoute = @'
Route::get(
    '/verify/surat/{code}',
    IssuedLetterVerificationController::class
)->name('issued-letters.verify');

'@

    $content = $content.Replace(
        $anchor,
        ($publicRoute + $anchor)
    )
}

Write-Utf8NoBom $routePath $content
Write-Host "[OK] Public verification route ditambahkan." -ForegroundColor Green

Write-Host ""
Write-Host "[OK] Phase 15 berhasil dipasang." -ForegroundColor Green
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan migrate"
Write-Host "php artisan view:clear"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact --filter=PhaseFifteenPublicVerificationTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
