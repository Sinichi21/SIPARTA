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
    [System.IO.File]::WriteAllText(
        $destination,
        $content,
        $utf8NoBom
    )

    Write-Host "[OK] $Relative" -ForegroundColor Green
}

# Pastikan Phase 7 ada sebelum overwrite enhancement.
if (-not (Test-Path "app\Models\LetterTemplate.php")) {
    throw "Phase 7 belum terdeteksi: app\Models\LetterTemplate.php tidak ada."
}

$targets = @(
    "app\Models\LetterheadProfile.php",
    "app\Livewire\AdministrationProfiles\Index.php",
    "app\Livewire\AdministrationProfiles\Create.php",
    "app\Livewire\AdministrationProfiles\Edit.php",
    "app\Models\LetterTemplate.php",
    "app\Services\LetterTemplateRenderer.php",
    "app\Livewire\LetterTemplates\Create.php",
    "app\Livewire\LetterTemplates\Edit.php",
    "database\migrations\2026_09_29_230000_create_letterhead_profiles_table.php",
    "database\migrations\2026_09_29_231000_add_letterhead_profile_to_letter_templates_table.php",
    "resources\views\components\letterhead-preview.blade.php",
    "resources\views\livewire\administration-profiles\_form.blade.php",
    "resources\views\livewire\administration-profiles\index.blade.php",
    "resources\views\livewire\administration-profiles\create.blade.php",
    "resources\views\livewire\administration-profiles\edit.blade.php",
    "resources\views\livewire\letter-templates\_form.blade.php",
    "resources\views\livewire\letter-templates\edit.blade.php",
    "tests\Feature\LetterheadAdministrationPhaseEightTest.php"
)

foreach ($target in $targets) {
    Write-Target $target
}

# ------------------------------------------------------------
# Routes
# ------------------------------------------------------------
$path = "routes\web.php"
$content = [System.IO.File]::ReadAllText((Resolve-Path $path))

if ($content -notmatch 'AdministrationProfiles\\Index') {
    $useBlock = @'
use App\Livewire\AdministrationProfiles\Create as AdministrationProfileCreate;
use App\Livewire\AdministrationProfiles\Edit as AdministrationProfileEdit;
use App\Livewire\AdministrationProfiles\Index as AdministrationProfileIndex;
'@

    $insertAt = $content.IndexOf("use App\Livewire\ActivityTypes")

    if ($insertAt -lt 0) {
        throw "Tidak menemukan area use statement routes."
    }

    $content = $content.Insert($insertAt, $useBlock)
}

if ($content -notmatch "name\('administration-profiles\.index'\)") {
    $block = @'

    /*
    |--------------------------------------------------------------------------
    | Kop & Administrasi Surat
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/administration-profiles',
        AdministrationProfileIndex::class
    )->name('administration-profiles.index');

    Route::get(
        '/administration-profiles/create',
        AdministrationProfileCreate::class
    )->name('administration-profiles.create');

    Route::get(
        '/administration-profiles/{letterheadProfile}/edit',
        AdministrationProfileEdit::class
    )->name('administration-profiles.edit');

'@

    $auditPos = $content.IndexOf("| Audit Log")

    if ($auditPos -ge 0) {
        $commentStart = $content.LastIndexOf(
            "    /*",
            $auditPos
        )

        if ($commentStart -ge 0) {
            $content = $content.Insert(
                $commentStart,
                $block
            )
        }
    }

    if ($content -notmatch "name\('administration-profiles\.index'\)") {
        $groupEnd = $content.LastIndexOf("});")

        if ($groupEnd -lt 0) {
            throw "Tidak menemukan akhir route auth group."
        }

        $content = $content.Insert(
            $groupEnd,
            $block
        )
    }
}

[System.IO.File]::WriteAllText(
    (Resolve-Path $path),
    $content,
    $utf8NoBom
)

Write-Host "[OK] routes\web.php" -ForegroundColor Green

# ------------------------------------------------------------
# Sidebar: sisip link di area Pengaturan tanpa bergantung exact block
# ------------------------------------------------------------
$path = "resources\views\components\app\sidebar.blade.php"
$content = [System.IO.File]::ReadAllText((Resolve-Path $path))

if ($content -notmatch 'administration-profiles\.\*') {
    $link = @'

        @can('settings.view')
            <div class="sidebar-section">
                <p class="sidebar-section-label">Administrasi Surat</p>

                <x-app.nav-link
                    :href="route('administration-profiles.index')"
                    :active="request()->routeIs('administration-profiles.*')"
                    icon="building"
                >
                    Kop & Administrasi
                </x-app.nav-link>
            </div>
        @endcan
'@

    $navClose = $content.LastIndexOf("</nav>")

    if ($navClose -lt 0) {
        throw "Tidak menemukan </nav> pada sidebar."
    }

    $content = $content.Insert(
        $navClose,
        $link + "`r`n"
    )

    [System.IO.File]::WriteAllText(
        (Resolve-Path $path),
        $content,
        $utf8NoBom
    )

    Write-Host "[OK] sidebar Kop & Administrasi" -ForegroundColor Green
}
else {
    Write-Host "[SKIP] sidebar Kop & Administrasi sudah ada" -ForegroundColor Yellow
}

# ------------------------------------------------------------
# storage link check
# ------------------------------------------------------------
if (-not (Test-Path "public\storage")) {
    Write-Host ""
    Write-Host "Membuat public storage symlink..." -ForegroundColor Cyan
    & php artisan storage:link

    if ($LASTEXITCODE -ne 0) {
        Write-Warning "storage:link gagal. Jalankan manual setelah installer."
    }
}

Write-Host ""
Write-Host "Phase 8 terpasang." -ForegroundColor Cyan
Write-Host ""
Write-Host "Lanjutkan:" -ForegroundColor Cyan
Write-Host "php artisan migrate"
Write-Host "php artisan view:clear"
Write-Host "php artisan test --compact --filter=LetterheadAdministrationPhaseEightTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
