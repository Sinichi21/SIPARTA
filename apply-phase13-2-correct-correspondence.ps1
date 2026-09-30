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
    "routes\web.php",
    "resources\views\livewire\incoming-letters\form.blade.php",
    "resources\views\livewire\outgoing-letters\form.blade.php",
    "app\Models\IncomingLetter.php",
    "app\Models\OutgoingLetter.php"
)

foreach ($file in $required) {
    if (-not (Test-Path $file)) {
        throw "Prasyarat tidak ditemukan: $file"
    }
}

$backupDir = "storage\app\phase-backups\phase13-2"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

foreach ($file in @(
    "routes\web.php",
    "app\Models\IncomingLetter.php",
    "app\Models\OutgoingLetter.php",
    "app\Livewire\IncomingLetters\Create.php",
    "app\Livewire\IncomingLetters\Edit.php",
    "app\Livewire\IncomingLetters\Show.php",
    "app\Livewire\OutgoingLetters\Create.php",
    "app\Livewire\OutgoingLetters\Edit.php",
    "resources\views\livewire\incoming-letters\form.blade.php",
    "resources\views\livewire\incoming-letters\show.blade.php",
    "resources\views\livewire\outgoing-letters\form.blade.php",
    "resources\views\livewire\outgoing-letters\show.blade.php"
)) {
    if (Test-Path $file) {
        $safe = $file -replace '[\\/:*?"<>|]', '_'
        Copy-Item $file "$backupDir\$stamp-$safe" -Force
    }
}

$targets = @(
    "app\Models\IncomingLetter.php",
    "app\Models\OutgoingLetter.php",
    "app\Services\OutgoingLetterTemplateRenderer.php",
    "app\Http\Controllers\OutgoingLetterDocumentController.php",
    "app\Livewire\IncomingLetters\Create.php",
    "app\Livewire\IncomingLetters\Edit.php",
    "app\Livewire\IncomingLetters\Show.php",
    "app\Livewire\OutgoingLetters\Create.php",
    "app\Livewire\OutgoingLetters\Edit.php",
    "resources\views\livewire\incoming-letters\form.blade.php",
    "resources\views\livewire\incoming-letters\show.blade.php",
    "resources\views\livewire\outgoing-letters\form.blade.php",
    "resources\views\livewire\outgoing-letters\show.blade.php",
    "resources\views\outgoing-letters\document-preview.blade.php",
    "database\migrations\2026_09_30_132000_move_template_placeholders_from_incoming_to_outgoing.php"
)

foreach ($target in $targets) {
    Copy-Source $target
}

# Remove incorrect Phase 13.1 Incoming-template artifacts.
$obsolete = @(
    "app\Services\IncomingLetterTemplateRenderer.php",
    "app\Http\Controllers\IncomingLetterDocumentController.php",
    "resources\views\incoming-letters\document-preview.blade.php",
    "tests\Feature\PhaseThirteenOneIncomingTemplateTest.php"
)

foreach ($file in $obsolete) {
    if (Test-Path $file) {
        Remove-Item $file -Force
        Write-Host "[CLEAN] $file" -ForegroundColor DarkYellow
    }
}

# Patch routes.
$path = "routes\web.php"
$content = Read-Utf8 $path

# Remove wrong incoming preview controller import.
$content = [regex]::Replace(
    $content,
    '(?m)^use App\\Http\\Controllers\\IncomingLetterDocumentController;\r?\n',
    ''
)

# Remove wrong incoming preview/print routes if present.
$content = [regex]::Replace(
    $content,
    '(?ms)\s*Route::get\(\s*''/surat-masuk/\{letter\}/document'',\s*\[IncomingLetterDocumentController::class,\s*''preview''\]\s*\)->name\(''incoming-letters\.document\.preview''\);\s*',
    "`r`n"
)

$content = [regex]::Replace(
    $content,
    '(?ms)\s*Route::get\(\s*''/surat-masuk/\{letter\}/document/print'',\s*\[IncomingLetterDocumentController::class,\s*''print''\]\s*\)->name\(''incoming-letters\.document\.print''\);\s*',
    "`r`n"
)

$outImport = "use App\Http\Controllers\OutgoingLetterDocumentController;"
if (-not $content.Contains($outImport)) {
    $anchor = "use App\Http\Controllers\LetterDocumentController;"
    if (-not $content.Contains($anchor)) {
        throw "Anchor import LetterDocumentController tidak ditemukan."
    }

    $content = $content.Replace(
        $anchor,
        ($outImport + "`r`n" + $anchor)
    )
}

if ($content -notmatch "outgoing-letters\.document\.preview") {
    $anchor = @'
    Route::get('/surat-keluar/{letter}/edit', OutgoingLetterEdit::class)
        ->name('outgoing-letters.edit');
'@

    if (-not $content.Contains($anchor)) {
        throw "Anchor route edit Surat Keluar tidak ditemukan."
    }

    $routes = @'

    Route::get(
        '/surat-keluar/{letter}/document',
        [OutgoingLetterDocumentController::class, 'preview']
    )->name('outgoing-letters.document.preview');

    Route::get(
        '/surat-keluar/{letter}/document/print',
        [OutgoingLetterDocumentController::class, 'print']
    )->name('outgoing-letters.document.print');
'@

    $content = $content.Replace(
        $anchor,
        ($anchor + $routes)
    )
}

Write-Utf8NoBom $path $content
Write-Host "[OK] Routes diarahkan ke preview Surat Keluar." -ForegroundColor Green

Write-Host ""
Write-Host "[OK] Koreksi Phase 13.2 selesai." -ForegroundColor Green
Write-Host "Surat Masuk: pencatatan + file opsional." -ForegroundColor Cyan
Write-Host "Surat Keluar: template + placeholder dinamis + preview/cetak." -ForegroundColor Cyan
Write-Host ""
Write-Host "Lanjutkan:" -ForegroundColor Cyan
Write-Host "php -l app\Models\IncomingLetter.php"
Write-Host "php -l app\Models\OutgoingLetter.php"
Write-Host "php -l app\Services\OutgoingLetterTemplateRenderer.php"
Write-Host "php -l app\Http\Controllers\OutgoingLetterDocumentController.php"
Write-Host "php -l app\Livewire\IncomingLetters\Create.php"
Write-Host "php -l app\Livewire\IncomingLetters\Edit.php"
Write-Host "php -l app\Livewire\OutgoingLetters\Create.php"
Write-Host "php -l app\Livewire\OutgoingLetters\Edit.php"
Write-Host "php artisan migrate"
Write-Host "php artisan view:clear"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
