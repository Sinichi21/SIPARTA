$ErrorActionPreference = "Stop"

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Read-Utf8 {
    param([string]$Path)
    return [System.IO.File]::ReadAllText((Resolve-Path $Path))
}

function Write-Utf8NoBom {
    param([string]$Path, [string]$Content)
    [System.IO.File]::WriteAllText((Resolve-Path $Path), $Content, $utf8NoBom)
}

# ============================================================
# 1. Pastikan relationship LetterType sudah ada
# ============================================================
$path = "app\Models\LetterType.php"
$content = Read-Utf8 $path

if ($content -notmatch 'public\s+function\s+templates\s*\(\s*\)\s*:\s*HasMany') {
    $method = @'

    public function templates(): HasMany
    {
        return $this->hasMany(LetterTemplate::class);
    }
'@

    $lastBrace = $content.LastIndexOf("}")

    if ($lastBrace -lt 0) {
        throw "Struktur class LetterType tidak valid."
    }

    $content = $content.Insert($lastBrace, $method)
    Write-Utf8NoBom $path $content
    Write-Host "[OK] LetterType::templates()" -ForegroundColor Green
}
else {
    Write-Host "[SKIP] LetterType::templates() sudah ada" -ForegroundColor Yellow
}

# ============================================================
# 2. Pastikan routes Template Surat sudah ada
# ============================================================
$path = "routes\web.php"
$content = Read-Utf8 $path

if ($content -notmatch 'LetterTemplates\\Index') {
    $needle = 'use App\Livewire\LetterTypes\Index as LetterTypeIndex;'

    if (-not $content.Contains($needle)) {
        throw "Tidak menemukan use LetterTypeIndex pada routes\web.php"
    }

    $replacement = @'
use App\Livewire\LetterTypes\Index as LetterTypeIndex;
use App\Livewire\LetterTemplates\Create as LetterTemplateCreate;
use App\Livewire\LetterTemplates\Edit as LetterTemplateEdit;
use App\Livewire\LetterTemplates\Index as LetterTemplateIndex;
'@

    $content = $content.Replace($needle, $replacement)
}

if ($content -notmatch "name\('letter-templates\.index'\)") {
    $auditMarker = "    /*`r`n    |--------------------------------------------------------------------------`r`n    | Audit Log"

    $routesBlock = @'
    /*
    |--------------------------------------------------------------------------
    | Letter Templates
    |--------------------------------------------------------------------------
    */

    Route::get('/letter-templates', LetterTemplateIndex::class)
        ->name('letter-templates.index');

    Route::get('/letter-templates/create', LetterTemplateCreate::class)
        ->name('letter-templates.create');

    Route::get(
        '/letter-templates/{letterTemplate}/edit',
        LetterTemplateEdit::class
    )->name('letter-templates.edit');


'@

    $pos = $content.IndexOf("    /*", $content.IndexOf("Audit Log") - 100)

    if ($content.Contains("Audit Log") -and $pos -ge 0) {
        $content = $content.Insert($pos, $routesBlock)
    }
    else {
        $groupEnd = $content.LastIndexOf("});")

        if ($groupEnd -lt 0) {
            throw "Tidak menemukan akhir route group."
        }

        $content = $content.Insert($groupEnd, $routesBlock)
    }
}

Write-Utf8NoBom $path $content
Write-Host "[OK] routes\web.php" -ForegroundColor Green

# ============================================================
# 3. Sidebar — robust: sisip section sebelum </nav>
# ============================================================
$path = "resources\views\components\app\sidebar.blade.php"
$content = Read-Utf8 $path

if ($content -notmatch 'letter-templates\.\*') {
    $templateSection = @'

        @can('settings.view')
            <div class="sidebar-section">
                <p class="sidebar-section-label">Pengaturan</p>

                <x-app.nav-link
                    :href="route('letter-templates.index')"
                    :active="request()->routeIs('letter-templates.*')"
                    icon="document"
                >
                    Template Surat
                </x-app.nav-link>
            </div>
        @endcan
'@

    $navClose = $content.LastIndexOf("</nav>")

    if ($navClose -lt 0) {
        throw "Tidak menemukan tag </nav> pada sidebar."
    }

    $content = $content.Insert($navClose, $templateSection + "`r`n")
    Write-Utf8NoBom $path $content
    Write-Host "[OK] Menu Template Surat disisipkan sebelum </nav>" -ForegroundColor Green
}
else {
    Write-Host "[SKIP] Menu Template Surat sudah ada" -ForegroundColor Yellow
}

# ============================================================
# 4. app.js import — idempotent
# ============================================================
$path = "resources\js\app.js"
$content = Read-Utf8 $path

if ($content -notmatch "letter-template-editor") {
    $content = $content.TrimEnd() + "`r`n`r`nimport './letter-template-editor.js';`r`n"
    Write-Utf8NoBom $path $content
    Write-Host "[OK] Import CKEditor bootstrap" -ForegroundColor Green
}
else {
    Write-Host "[SKIP] CKEditor bootstrap sudah di-import" -ForegroundColor Yellow
}

# ============================================================
# 5. CKEditor dependency
# ============================================================
Write-Host ""
Write-Host "Memastikan dependency CKEditor 5 tersedia..." -ForegroundColor Cyan

& npm install ckeditor5

if ($LASTEXITCODE -ne 0) {
    throw "npm install ckeditor5 gagal."
}

Write-Host ""
Write-Host "[OK] Repair Phase 7 v2 selesai." -ForegroundColor Green
Write-Host ""
Write-Host "Lanjutkan dengan:" -ForegroundColor Cyan
Write-Host "php -l app\Models\LetterType.php"
Write-Host "php -l app\Models\LetterTemplate.php"
Write-Host "php -l app\Services\LetterTemplateRenderer.php"
Write-Host "php -l app\Livewire\LetterTemplates\Index.php"
Write-Host "php -l app\Livewire\LetterTemplates\Create.php"
Write-Host "php -l app\Livewire\LetterTemplates\Edit.php"
Write-Host "php artisan migrate"
Write-Host "php artisan view:clear"
Write-Host "php artisan test --compact --filter=LetterTemplatePhaseSevenTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
