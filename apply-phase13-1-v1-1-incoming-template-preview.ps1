$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Read-Utf8([string]$Path) {
    return [System.IO.File]::ReadAllText((Resolve-Path $Path))
}

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    [System.IO.File]::WriteAllText(
        (Resolve-Path $Path),
        $Content,
        $utf8NoBom
    )
}

function Write-Target([string]$Relative) {
    $source = Join-Path $PSScriptRoot $Relative
    $destination = Join-Path (Get-Location) $Relative
    $parent = Split-Path $destination -Parent

    if (-not (Test-Path $parent)) {
        New-Item -ItemType Directory -Force -Path $parent | Out-Null
    }

    $content = [System.IO.File]::ReadAllText($source)

    if (Test-Path $destination) {
        [System.IO.File]::WriteAllText(
            (Resolve-Path $destination),
            $content,
            $utf8NoBom
        )
    } else {
        [System.IO.File]::WriteAllText(
            $destination,
            $content,
            $utf8NoBom
        )
    }

    Write-Host "[OK] $Relative" -ForegroundColor Green
}

$required = @(
    "app\Models\IncomingLetter.php",
    "app\Livewire\IncomingLetters\Create.php",
    "app\Livewire\IncomingLetters\Edit.php",
    "app\Livewire\IncomingLetters\Show.php",
    "resources\views\livewire\incoming-letters\form.blade.php",
    "resources\views\livewire\incoming-letters\show.blade.php",
    "app\Services\LetterTemplateRenderer.php",
    "routes\web.php"
)

foreach ($file in $required) {
    if (-not (Test-Path $file)) {
        throw "Prasyarat Phase 13.1 tidak ditemukan: $file"
    }
}

$form = Read-Utf8 "resources\views\livewire\incoming-letters\form.blade.php"

if (
    ($form -notmatch 'correspondence-page') -or
    ($form -notmatch 'correspondence-section')
) {
    throw "Form Surat Masuk lokal tidak cocok dengan tampilan terbaru. Installer dihentikan agar gaya yang sudah dirapikan tidak tertimpa."
}

$backupDir = "storage\app\phase-backups\phase13-1"
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

foreach ($file in @(
    "app\Models\IncomingLetter.php",
    "app\Livewire\IncomingLetters\Create.php",
    "app\Livewire\IncomingLetters\Edit.php",
    "app\Livewire\IncomingLetters\Show.php",
    "resources\views\livewire\incoming-letters\form.blade.php",
    "resources\views\livewire\incoming-letters\show.blade.php",
    "routes\web.php"
)) {
    $safeName = ($file -replace '[\\/:*?"<>|]', '_')
    Copy-Item $file "$backupDir\$stamp-$safeName" -Force
}

Write-Host "[OK] Backup file Surat Masuk dibuat." -ForegroundColor Green

$targets = @(
    "app\Models\IncomingLetter.php",
    "app\Services\IncomingLetterTemplateRenderer.php",
    "app\Http\Controllers\IncomingLetterDocumentController.php",
    "app\Livewire\IncomingLetters\Create.php",
    "app\Livewire\IncomingLetters\Edit.php",
    "app\Livewire\IncomingLetters\Show.php",
    "resources\views\livewire\incoming-letters\form.blade.php",
    "resources\views\livewire\incoming-letters\show.blade.php",
    "resources\views\incoming-letters\document-preview.blade.php",
    "database\migrations\2026_09_30_131000_add_template_fields_to_incoming_letters_table.php",
    "tests\Feature\PhaseThirteenOneIncomingTemplateTest.php",
    "docs\PHASE13-1.md"
)

foreach ($target in $targets) {
    Write-Target $target
}

# Routes: import controller + preview/print routes.
$path = "routes\web.php"
$content = Read-Utf8 $path

$controllerImport =
    "use App\Http\Controllers\IncomingLetterDocumentController;"

if (-not $content.Contains($controllerImport)) {
    $anchor =
        "use App\Http\Controllers\LetterDocumentController;"

    if (-not $content.Contains($anchor)) {
        $anchor =
            "use Illuminate\Support\Facades\Route;"
    }

    if (-not $content.Contains($anchor)) {
        throw "Anchor import controller tidak ditemukan."
    }

    $content = $content.Replace(
        $anchor,
        ($controllerImport + "`r`n" + $anchor)
    )
}

if (
    $content -notmatch "incoming-letters\.document\.preview"
) {
    $anchor = @'
    Route::get('/surat-masuk/{letter}/edit', IncomingLetterEdit::class)
        ->name('incoming-letters.edit');
'@

    if (-not $content.Contains($anchor)) {
        throw "Anchor route edit Surat Masuk tidak ditemukan."
    }

    $routes = @'

    Route::get(
        '/surat-masuk/{letter}/document',
        [IncomingLetterDocumentController::class, 'preview']
    )->name('incoming-letters.document.preview');

    Route::get(
        '/surat-masuk/{letter}/document/print',
        [IncomingLetterDocumentController::class, 'print']
    )->name('incoming-letters.document.print');
'@

    $content = $content.Replace(
        $anchor,
        $anchor + $routes
    )
}

Write-Utf8NoBom $path $content
Write-Host "[OK] Route Preview/Cetak Surat Masuk ditambahkan." -ForegroundColor Green

Write-Host ""
Write-Host "[OK] Phase 13.1 selesai diterapkan." -ForegroundColor Green
Write-Host ""
Write-Host "Tidak ada perubahan pada resources/css/app.css." -ForegroundColor Cyan
Write-Host ""
Write-Host "Lanjutkan:" -ForegroundColor Cyan
Write-Host "php -l app\Models\IncomingLetter.php"
Write-Host "php -l app\Services\IncomingLetterTemplateRenderer.php"
Write-Host "php -l app\Http\Controllers\IncomingLetterDocumentController.php"
Write-Host "php -l app\Livewire\IncomingLetters\Create.php"
Write-Host "php -l app\Livewire\IncomingLetters\Edit.php"
Write-Host "php -l app\Livewire\IncomingLetters\Show.php"
Write-Host "php artisan migrate"
Write-Host "php artisan view:clear"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact --filter=PhaseThirteenOneIncomingTemplateTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
