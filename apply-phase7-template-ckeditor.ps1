$ErrorActionPreference = "Stop"

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Write-Target {
    param([string]$Relative)

    $source = Join-Path $PSScriptRoot $Relative
    $destination = Join-Path (Get-Location) $Relative

    $parent = Split-Path $destination -Parent
    if (-not (Test-Path $parent)) {
        New-Item -ItemType Directory -Force -Path $parent | Out-Null
    }

    $content = [System.IO.File]::ReadAllText($source)
    [System.IO.File]::WriteAllText($destination, $content, $utf8NoBom)

    Write-Host "[OK] $Relative" -ForegroundColor Green
}

$files = @(
    "app\Models\LetterTemplate.php",
    "app\Services\LetterTemplateRenderer.php",
    "app\Livewire\LetterTemplates\Index.php",
    "app\Livewire\LetterTemplates\Create.php",
    "app\Livewire\LetterTemplates\Edit.php",
    "database\migrations\2026_09_29_220000_create_letter_templates_table.php",
    "resources\views\livewire\letter-templates\index.blade.php",
    "resources\views\livewire\letter-templates\_form.blade.php",
    "resources\views\livewire\letter-templates\create.blade.php",
    "resources\views\livewire\letter-templates\edit.blade.php",
    "resources\js\letter-template-editor.js",
    "tests\Feature\LetterTemplatePhaseSevenTest.php"
)

foreach ($file in $files) {
    Write-Target $file
}

# LetterType relationship
$path = "app\Models\LetterType.php"
$content = [System.IO.File]::ReadAllText((Resolve-Path $path))

if (-not $content.Contains("public function templates(): HasMany")) {
    $old = @'
    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class);
    }
'@

    $new = @'
    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(LetterTemplate::class);
    }
'@

    if (-not $content.Contains($old)) {
        throw "Gagal patch relationship LetterType."
    }

    $content = $content.Replace($old, $new)
    [System.IO.File]::WriteAllText((Resolve-Path $path), $content, $utf8NoBom)
    Write-Host "[OK] LetterType::templates()" -ForegroundColor Green
}

# Routes
$path = "routes\web.php"
$content = [System.IO.File]::ReadAllText((Resolve-Path $path))

if (-not $content.Contains("LetterTemplates\Index")) {
    $needle = "use App\Livewire\LetterTypes\Index as LetterTypeIndex;"

    $replacement = @'
use App\Livewire\LetterTypes\Index as LetterTypeIndex;
use App\Livewire\LetterTemplates\Create as LetterTemplateCreate;
use App\Livewire\LetterTemplates\Edit as LetterTemplateEdit;
use App\Livewire\LetterTemplates\Index as LetterTemplateIndex;
'@

    if (-not $content.Contains($needle)) {
        throw "Gagal patch use statements route."
    }

    $content = $content.Replace($needle, $replacement)
}

if (-not $content.Contains("letter-templates.index")) {
    $needle = @'
    /*
    |--------------------------------------------------------------------------
    | Audit Log
    |--------------------------------------------------------------------------
    */
'@

    $replacement = @'
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

    /*
    |--------------------------------------------------------------------------
    | Audit Log
    |--------------------------------------------------------------------------
    */
'@

    if (-not $content.Contains($needle)) {
        throw "Gagal patch route template."
    }

    $content = $content.Replace($needle, $replacement)
}

[System.IO.File]::WriteAllText((Resolve-Path $path), $content, $utf8NoBom)
Write-Host "[OK] routes\web.php" -ForegroundColor Green

# Sidebar
$path = "resources\views\components\app\sidebar.blade.php"
$content = [System.IO.File]::ReadAllText((Resolve-Path $path))

if (-not $content.Contains("letter-templates.*")) {
    $needle = @'
        @can('audit-logs.view')
            <div class="sidebar-section"><p class="sidebar-section-label">Pengaturan</p><x-app.nav-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')" icon="clock">Log Aktivitas</x-app.nav-link></div>
        @endcan
'@

    $replacement = @'
        @canany(['settings.view', 'audit-logs.view'])
            <div class="sidebar-section">
                <p class="sidebar-section-label">Pengaturan</p>

                @can('settings.view')
                    <x-app.nav-link
                        :href="route('letter-templates.index')"
                        :active="request()->routeIs('letter-templates.*')"
                        icon="document"
                    >
                        Template Surat
                    </x-app.nav-link>
                @endcan

                @can('audit-logs.view')
                    <x-app.nav-link
                        :href="route('audit-logs.index')"
                        :active="request()->routeIs('audit-logs.*')"
                        icon="clock"
                    >
                        Log Aktivitas
                    </x-app.nav-link>
                @endcan
            </div>
        @endcanany
'@

    if (-not $content.Contains($needle)) {
        throw "Gagal patch sidebar."
    }

    $content = $content.Replace($needle, $replacement)
    [System.IO.File]::WriteAllText((Resolve-Path $path), $content, $utf8NoBom)
    Write-Host "[OK] sidebar Template Surat" -ForegroundColor Green
}

# app.js import
$path = "resources\js\app.js"
$content = [System.IO.File]::ReadAllText((Resolve-Path $path))

if (-not $content.Contains("letter-template-editor")) {
    $content = $content.TrimEnd() + "`r`n`r`nimport './letter-template-editor.js';`r`n"
    [System.IO.File]::WriteAllText((Resolve-Path $path), $content, $utf8NoBom)
    Write-Host "[OK] Import CKEditor bootstrap" -ForegroundColor Green
}

Write-Host ""
Write-Host "Menginstall dependency CKEditor 5..." -ForegroundColor Cyan
& npm install ckeditor5
if ($LASTEXITCODE -ne 0) {
    throw "npm install ckeditor5 gagal. Jalankan manual 'npm install ckeditor5' lalu ulangi build."
}

Write-Host ""
Write-Host "Phase 7 — Template Surat + CKEditor 5 terpasang." -ForegroundColor Cyan
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan migrate"
Write-Host "php artisan view:clear"
Write-Host "php artisan test --compact --filter=LetterTemplatePhaseSevenTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
