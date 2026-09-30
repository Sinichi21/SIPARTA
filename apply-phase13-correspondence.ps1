$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Read-Utf8([string]$Path) {
    return [System.IO.File]::ReadAllText((Resolve-Path $Path))
}

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    [System.IO.File]::WriteAllText((Resolve-Path $Path), $Content, $utf8NoBom)
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
        [System.IO.File]::WriteAllText((Resolve-Path $destination), $content, $utf8NoBom)
    } else {
        [System.IO.File]::WriteAllText($destination, $content, $utf8NoBom)
    }

    Write-Host "[OK] $Relative" -ForegroundColor Green
}

# ------------------------------------------------------------
# Preconditions
# ------------------------------------------------------------
$required = @(
    "routes\web.php",
    "database\seeders\RolePermissionSeeder.php",
    "resources\views\components\app\sidebar.blade.php",
    "resources\views\components\app\page-heading.blade.php",
    "resources\views\components\app\stat-card.blade.php",
    "resources\views\components\app\empty-state.blade.php",
    "app\Services\AuditService.php",
    "app\Models\LetterType.php",
    "app\Models\LetterTemplate.php",
    "app\Models\LetterheadProfile.php"
)

foreach ($file in $required) {
    if (-not (Test-Path $file)) {
        throw "Prasyarat Phase 13 tidak ditemukan: $file"
    }
}

$backupDir = "storage\app\phase-backups\phase13"
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

foreach ($file in @(
    "routes\web.php",
    "database\seeders\RolePermissionSeeder.php",
    "resources\views\components\app\sidebar.blade.php"
)) {
    $name = Split-Path $file -Leaf
    Copy-Item $file "$backupDir\$stamp-$name" -Force
}

Write-Host "[OK] Backup routes, seeder, dan sidebar dibuat." -ForegroundColor Green

# ------------------------------------------------------------
# Copy Phase 13 files
# ------------------------------------------------------------
$targets = @(
    "app\Enums\IncomingLetterStatus.php",
    "app\Enums\OutgoingLetterStatus.php",
    "app\Models\IncomingLetter.php",
    "app\Models\OutgoingLetter.php",
    "app\Models\IssuedLetter.php",
    "app\Services\IncomingLetterService.php",
    "app\Services\OutgoingLetterService.php",
    "app\Livewire\IncomingLetters\Index.php",
    "app\Livewire\IncomingLetters\Create.php",
    "app\Livewire\IncomingLetters\Edit.php",
    "app\Livewire\IncomingLetters\Show.php",
    "app\Livewire\OutgoingLetters\Index.php",
    "app\Livewire\OutgoingLetters\Create.php",
    "app\Livewire\OutgoingLetters\Edit.php",
    "app\Livewire\OutgoingLetters\Show.php",
    "app\Livewire\IssuedLetters\Index.php",
    "app\Livewire\IssuedLetters\Show.php",
    "resources\views\livewire\incoming-letters\index.blade.php",
    "resources\views\livewire\incoming-letters\create.blade.php",
    "resources\views\livewire\incoming-letters\edit.blade.php",
    "resources\views\livewire\incoming-letters\show.blade.php",
    "resources\views\livewire\outgoing-letters\index.blade.php",
    "resources\views\livewire\outgoing-letters\create.blade.php",
    "resources\views\livewire\outgoing-letters\edit.blade.php",
    "resources\views\livewire\outgoing-letters\show.blade.php",
    "resources\views\livewire\issued-letters\index.blade.php",
    "resources\views\livewire\issued-letters\show.blade.php",
    "database\migrations\2026_09_30_130000_create_incoming_letters_table.php",
    "database\migrations\2026_09_30_130010_create_outgoing_letters_table.php",
    "database\migrations\2026_09_30_130020_create_issued_letters_table.php",
    "tests\Feature\PhaseThirteenCorrespondenceTest.php",
    "docs\PHASE13.md"
)

foreach ($target in $targets) {
    Write-Target $target
}

# ------------------------------------------------------------
# Route patch
# ------------------------------------------------------------
$path = "routes\web.php"
$content = Read-Utf8 $path

# Tidy one known formatting artifact from previous commit.
$content = $content.Replace(
    "use App\Livewire\AdministrationProfiles\Index as AdministrationProfileIndex;use App\Livewire\ActivityTypes\Create as ActivityTypeCreate;",
    "use App\Livewire\AdministrationProfiles\Index as AdministrationProfileIndex;`r`nuse App\Livewire\ActivityTypes\Create as ActivityTypeCreate;"
)

$routeImport = "use Illuminate\Support\Facades\Route;"

$imports = @(
    "use App\Livewire\IncomingLetters\Index as IncomingLetterIndex;",
    "use App\Livewire\IncomingLetters\Create as IncomingLetterCreate;",
    "use App\Livewire\IncomingLetters\Edit as IncomingLetterEdit;",
    "use App\Livewire\IncomingLetters\Show as IncomingLetterShow;",
    "use App\Livewire\OutgoingLetters\Index as OutgoingLetterIndex;",
    "use App\Livewire\OutgoingLetters\Create as OutgoingLetterCreate;",
    "use App\Livewire\OutgoingLetters\Edit as OutgoingLetterEdit;",
    "use App\Livewire\OutgoingLetters\Show as OutgoingLetterShow;",
    "use App\Livewire\IssuedLetters\Index as IssuedLetterIndex;",
    "use App\Livewire\IssuedLetters\Show as IssuedLetterShow;"
)

foreach ($import in $imports) {
    if (-not $content.Contains($import)) {
        if (-not $content.Contains($routeImport)) {
            throw "Anchor import Route tidak ditemukan."
        }

        $content = $content.Replace(
            $routeImport,
            $import + "`r`n" + $routeImport
        )
    }
}

if ($content -notmatch "name\('incoming-letters\.index'\)") {
    $anchor = "    /*`r`n    |--------------------------------------------------------------------------`r`n    | SPT"

    if (-not $content.Contains($anchor)) {
        $anchor = "    /*`n    |--------------------------------------------------------------------------`n    | SPT"
    }

    if (-not $content.Contains($anchor)) {
        throw "Anchor blok SPT pada routes/web.php tidak ditemukan."
    }

    $block = @'
    /*
    |--------------------------------------------------------------------------
    | Persuratan
    |--------------------------------------------------------------------------
    */

    Route::get('/surat-masuk', IncomingLetterIndex::class)
        ->name('incoming-letters.index');

    Route::get('/surat-masuk/create', IncomingLetterCreate::class)
        ->name('incoming-letters.create');

    Route::get('/surat-masuk/{letter}', IncomingLetterShow::class)
        ->name('incoming-letters.show');

    Route::get('/surat-masuk/{letter}/edit', IncomingLetterEdit::class)
        ->name('incoming-letters.edit');

    Route::get('/surat-keluar', OutgoingLetterIndex::class)
        ->name('outgoing-letters.index');

    Route::get('/surat-keluar/create', OutgoingLetterCreate::class)
        ->name('outgoing-letters.create');

    Route::get('/surat-keluar/{letter}', OutgoingLetterShow::class)
        ->name('outgoing-letters.show');

    Route::get('/surat-keluar/{letter}/edit', OutgoingLetterEdit::class)
        ->name('outgoing-letters.edit');

    Route::get('/surat-terbit', IssuedLetterIndex::class)
        ->name('issued-letters.index');

    Route::get('/surat-terbit/{letter}', IssuedLetterShow::class)
        ->name('issued-letters.show');


'@

    $content = $content.Replace($anchor, $block + $anchor)
}

Write-Utf8NoBom $path $content
Write-Host "[OK] Routes Phase 13 ditambahkan." -ForegroundColor Green

# ------------------------------------------------------------
# Permission seeder patch
# ------------------------------------------------------------
$path = "database\seeders\RolePermissionSeeder.php"
$content = Read-Utf8 $path

if ($content -notmatch "'incoming-letters\.view'") {
    $anchor = "            'letters.view',"

    if (-not $content.Contains($anchor)) {
        throw "Anchor permission letters.view tidak ditemukan."
    }

    $permissions = @'
            'incoming-letters.view',
            'incoming-letters.create',
            'incoming-letters.update',
            'incoming-letters.process',
            'incoming-letters.archive',

            'outgoing-letters.view',
            'outgoing-letters.create',
            'outgoing-letters.update',
            'outgoing-letters.verify',
            'outgoing-letters.approve',
            'outgoing-letters.number',
            'outgoing-letters.publish',
            'outgoing-letters.send',
            'outgoing-letters.archive',

            'issued-letters.view',

'@

    $content = $content.Replace(
        $anchor,
        $permissions + $anchor
    )
}

if ($content -notmatch "\$staff->syncPermissions") {
    $anchor = "        app(PermissionRegistrar::class)->forgetCachedPermissions();"

    $last = $content.LastIndexOf($anchor)

    if ($last -lt 0) {
        throw "Anchor akhir RolePermissionSeeder tidak ditemukan."
    }

    $matrix = @'
        $staff = Role::findByName('staff', $guard);
        $staff->syncPermissions([
            'my-dashboard.view',
            'my-letters.view',
            'my-reports.view',
        ]);

        Role::findByName('admin-persuratan', $guard)
            ->givePermissionTo([
                'incoming-letters.view',
                'incoming-letters.create',
                'incoming-letters.update',
                'incoming-letters.process',
                'incoming-letters.archive',
                'outgoing-letters.view',
                'outgoing-letters.create',
                'outgoing-letters.update',
                'outgoing-letters.verify',
                'outgoing-letters.approve',
                'outgoing-letters.number',
                'outgoing-letters.publish',
                'outgoing-letters.send',
                'outgoing-letters.archive',
                'issued-letters.view',
            ]);

        Role::findByName('operator', $guard)
            ->givePermissionTo([
                'incoming-letters.view',
                'incoming-letters.create',
                'incoming-letters.update',
                'outgoing-letters.view',
                'outgoing-letters.create',
                'outgoing-letters.update',
                'issued-letters.view',
            ]);

        Role::findByName('verifikator', $guard)
            ->givePermissionTo([
                'incoming-letters.view',
                'incoming-letters.process',
                'outgoing-letters.view',
                'outgoing-letters.verify',
                'issued-letters.view',
            ]);

        Role::findByName('pimpinan', $guard)
            ->givePermissionTo([
                'incoming-letters.view',
                'incoming-letters.process',
                'incoming-letters.archive',
                'outgoing-letters.view',
                'outgoing-letters.approve',
                'outgoing-letters.number',
                'outgoing-letters.publish',
                'outgoing-letters.send',
                'outgoing-letters.archive',
                'issued-letters.view',
            ]);

        Role::findByName('viewer', $guard)
            ->givePermissionTo([
                'incoming-letters.view',
                'outgoing-letters.view',
                'issued-letters.view',
            ]);

'@

    $content = $content.Insert($last, $matrix)
}

Write-Utf8NoBom $path $content
Write-Host "[OK] Permission dan role matrix Phase 13 ditambahkan." -ForegroundColor Green

# ------------------------------------------------------------
# Sidebar patch - preserve current design, add only one group.
# ------------------------------------------------------------
$path = "resources\views\components\app\sidebar.blade.php"
$content = Read-Utf8 $path

if ($content -notmatch "'id'\s*=>\s*'correspondence'") {
    $anchor = "            ['id' => 'spt', 'label' => 'Surat Perintah Tugas'"

    $idx = $content.IndexOf($anchor)

    if ($idx -lt 0) {
        throw "Anchor group Surat Perintah Tugas tidak ditemukan. Sidebar tidak diubah."
    }

    $group = @'
            ['id' => 'correspondence', 'label' => 'Persuratan', 'icon' => 'document', 'section' => 'work', 'items' => [
                ['label' => 'Surat Masuk', 'route' => 'incoming-letters.index', 'match' => 'incoming-letters.*', 'permission' => 'incoming-letters.view'],
                ['label' => 'Surat Keluar', 'route' => 'outgoing-letters.index', 'match' => 'outgoing-letters.*', 'permission' => 'outgoing-letters.view'],
                ['label' => 'Surat Terbit', 'route' => 'issued-letters.index', 'match' => 'issued-letters.*', 'permission' => 'issued-letters.view'],
            ]],
'@

    $content = $content.Insert($idx, $group)
}

Write-Utf8NoBom $path $content
Write-Host "[OK] Group Persuratan ditambahkan tanpa mengganti desain sidebar." -ForegroundColor Green

# ------------------------------------------------------------
# Cleanup known root artifacts from repair/install history.
# Only root copies are removed; canonical app/database/tests files remain.
# ------------------------------------------------------------
$cleanup = @(
    "2026_09_30_090000_add_security_fields_to_users_table.php",
    "Dashboard.php",
    "FortifyServiceProvider.php",
    "PhaseTwelveOneV2Test.php",
    "apply-phase12-1-v2-1-sidebar-safe.ps1",
    "apply-phase12-1-v2-sidebar-safe.ps1",
    "repair-phase12-1-v2-2-sidebar.ps1",
    "repair-phase12-1-v2-3-test.ps1",
    "repair-phase12-1-v2-4-dashboard-render.ps1",
    "phpstan-test.zip",
    "rename_livewire_files.php",
    "spt-data-integrity-phase2.patch"
)

foreach ($artifact in $cleanup) {
    if (Test-Path $artifact) {
        Remove-Item $artifact -Force
        Write-Host "[CLEAN] $artifact" -ForegroundColor DarkYellow
    }
}

Write-Host ""
Write-Host "[OK] Phase 13 terpasang." -ForegroundColor Green
Write-Host ""
Write-Host "Lanjutkan:" -ForegroundColor Cyan
Write-Host "php -l app\Models\IncomingLetter.php"
Write-Host "php -l app\Models\OutgoingLetter.php"
Write-Host "php -l app\Models\IssuedLetter.php"
Write-Host "php -l app\Services\IncomingLetterService.php"
Write-Host "php -l app\Services\OutgoingLetterService.php"
Write-Host "php artisan migrate"
Write-Host "php artisan db:seed --class=RolePermissionSeeder"
Write-Host "php artisan permission:cache-reset"
Write-Host "php artisan view:clear"
Write-Host "php artisan test --compact --filter=PhaseThirteenCorrespondenceTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
