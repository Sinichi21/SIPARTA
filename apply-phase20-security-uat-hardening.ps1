$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Read-Utf8([string]$Path) {
    return [System.IO.File]::ReadAllText((Resolve-Path $Path))
}

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    [System.IO.File]::WriteAllText((Resolve-Path $Path), $Content, $utf8NoBom)
}

$required = @(
    "routes\web.php",
    "app\Http\Controllers\LetterDocumentController.php",
    "resources\views\letters\linked-document-preview.blade.php",
    "resources\views\livewire\letters\show.blade.php",
    "app\Livewire\OutgoingLetters\Show.php"
)

foreach ($file in $required) {
    if (-not (Test-Path $file)) {
        throw "Prasyarat Phase 20 tidak ditemukan: $file"
    }
}

# Guard latest commit features: do not apply against pre-modification SPT document flow.
$letterDocument = Read-Utf8 "app\Http\Controllers\LetterDocumentController.php"

if (
    ($letterDocument -notmatch 'linkedDocument') -or
    ($letterDocument -notmatch 'archivedPdfBinary') -or
    ($letterDocument -notmatch 'linked-document-preview')
) {
    throw "Flow preview/cetak/download Detail SPT terbaru belum terdeteksi. Patch dihentikan agar modifikasi terbaru tidak tertimpa."
}

$sptShow = Read-Utf8 "resources\views\livewire\letters\show.blade.php"

if (
    ($sptShow -notmatch 'Lihat Surat Keluar') -or
    ($sptShow -notmatch 'letters.document.preview') -or
    ($sptShow -notmatch 'letters.document.print') -or
    ($sptShow -notmatch 'letters.document.pdf')
) {
    throw "Tampilan Detail SPT terbaru belum terdeteksi. Patch dihentikan."
}

$backupDir = "storage\app\phase-backups\phase20"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

Copy-Item "routes\web.php" "$backupDir\$stamp-routes_web.php" -Force

# Add rate limit only to the public verification endpoint.
$routePath = "routes\web.php"
$content = Read-Utf8 $routePath

$old = @'
Route::get(
    '/verify/surat/{code}',
    IssuedLetterVerificationController::class
)->name('issued-letters.verify');
'@

$new = @'
Route::get(
    '/verify/surat/{code}',
    IssuedLetterVerificationController::class
)->middleware('throttle:60,1')
    ->name('issued-letters.verify');
'@

if ($content.Contains($old)) {
    $content = $content.Replace($old, $new)
    Write-Utf8NoBom $routePath $content
    Write-Host "[OK] Rate limit public verification" -ForegroundColor Green
} elseif ($content -match "throttle:60,1" -and $content -match "issued-letters\.verify") {
    Write-Host "[SKIP] Public verification sudah memiliki rate limit." -ForegroundColor Yellow
} else {
    throw "Anchor route public verification tidak ditemukan."
}

foreach ($relative in @(
    "tests\Feature\PhaseTwentySecurityUatTest.php",
    "docs\PHASE20.md"
)) {
    $source = Join-Path $PSScriptRoot $relative
    $target = Join-Path (Get-Location) $relative
    $parent = Split-Path $target -Parent

    if (-not (Test-Path $parent)) {
        New-Item -ItemType Directory -Force -Path $parent | Out-Null
    }

    $fileContent = [System.IO.File]::ReadAllText($source)

    if (Test-Path $target) {
        [System.IO.File]::WriteAllText((Resolve-Path $target), $fileContent, $utf8NoBom)
    } else {
        [System.IO.File]::WriteAllText($target, $fileContent, $utf8NoBom)
    }

    Write-Host "[OK] $relative" -ForegroundColor Green
}

Write-Host ""
Write-Host "[OK] Phase 20 Security & UAT Hardening terpasang." -ForegroundColor Green
Write-Host "Preview/cetak/download Detail SPT dan UI terbaru tidak diubah." -ForegroundColor Cyan
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan view:clear"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact --filter=PhaseTwentySecurityUatTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
