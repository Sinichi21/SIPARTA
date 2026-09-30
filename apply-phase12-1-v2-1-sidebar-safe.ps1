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

# ------------------------------------------------------------
# Preconditions
# ------------------------------------------------------------
$required = @(
    "resources\views\components\app\sidebar.blade.php",
    "resources\views\components\app\sidebar-group.blade.php",
    "app\Livewire\Users\Edit.php",
    "app\Livewire\Roles\Edit.php",
    "app\Livewire\Security\AccountRecoveryIndex.php",
    "database\seeders\RolePermissionSeeder.php"
)

foreach ($file in $required) {
    if (-not (Test-Path $file)) {
        throw "File prasyarat tidak ditemukan: $file"
    }
}

$sidebarPath = "resources\views\components\app\sidebar.blade.php"
$sidebar = Read-Utf8 $sidebarPath

if (
    ($sidebar -notmatch '\$groups\s*=\s*collect') -or
    ($sidebar -notmatch 'x-app\.sidebar-group')
) {
    throw "Sidebar lokal tidak cocok dengan desain sidebar terbaru. Installer dihentikan tanpa mengubah sidebar."
}

# Backup sidebar latest before any patch.
$backupDir = "storage\app\phase-backups"
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
}

Copy-Item `
    $sidebarPath `
    "$backupDir\sidebar-before-phase12-1-v2.blade.php" `
    -Force

Write-Host "[OK] Backup sidebar dibuat" -ForegroundColor Green

# ------------------------------------------------------------
# Add/replace new backend files. No CSS/layout/sidebar replacement.
# ------------------------------------------------------------
$targets = @(
    "app\Support\PersonalLetterAccess.php",
    "app\Livewire\Dashboard.php",
    "app\Livewire\MySpt\Index.php",
    "app\Livewire\MySpt\Show.php",
    "app\Livewire\MyRecap\Index.php",
    "app\Livewire\Users\Index.php",
    "app\Livewire\Roles\Index.php",
    "app\Services\AccountSecurityService.php",
    "resources\views\livewire\dashboard-personal.blade.php",
    "resources\views\livewire\my-spt\index.blade.php",
    "resources\views\livewire\my-spt\show.blade.php",
    "resources\views\livewire\my-recap\index.blade.php",
    "tests\Feature\PhaseTwelveOneV2Test.php",
    "docs\PHASE12-1-V2.md"
)

foreach ($target in $targets) {
    Write-Target $target
}

# ------------------------------------------------------------
# User model formatting only: fix accidental final "}}" if present.
# ------------------------------------------------------------
$path = "app\Models\User.php"
$content = Read-Utf8 $path

if ($content.TrimEnd().EndsWith("}}")) {
    $trimmed = $content.TrimEnd()
    $trimmed = $trimmed.Substring(
        0,
        $trimmed.Length - 2
    ) + "}`r`n}"

    [System.IO.File]::WriteAllText(
        (Resolve-Path $path),
        $trimmed + "`r`n",
        $utf8NoBom
    )

    Write-Host "[OK] Formatting akhir User.php dirapikan" -ForegroundColor Green
}

# ------------------------------------------------------------
# Users/Edit: non-super-admin may not discover/edit super-admin.
# ------------------------------------------------------------
$path = "app\Livewire\Users\Edit.php"
$content = Read-Utf8 $path

if ($content -notmatch "abort_if\(\s*\$user->hasRole\('super-admin'\)") {
    $needle = "        Gate::authorize('users.update');"
    $pos = $content.IndexOf($needle)

    if ($pos -lt 0) {
        throw "Anchor Users/Edit tidak ditemukan."
    }

    $insertPos = $pos + $needle.Length

    $guard = @'

        abort_if(
            $user->hasRole('super-admin')
            && ! auth()->user()->hasRole('super-admin'),
            404
        );
'@

    $content = $content.Insert(
        $insertPos,
        $guard
    )

    Write-Utf8NoBom $path $content
    Write-Host "[OK] User super-admin direct URL isolation" -ForegroundColor Green
}

# ------------------------------------------------------------
# Roles/Edit: non-super-admin cannot discover role; system role not editable.
# ------------------------------------------------------------
$path = "app\Livewire\Roles\Edit.php"
$content = Read-Utf8 $path

if ($content -notmatch "abort_if\(\s*\$role->name === 'super-admin'\s*&&") {
    $needle = "        Gate::authorize('roles.manage');"
    $pos = $content.IndexOf($needle)

    if ($pos -lt 0) {
        throw "Anchor Roles/Edit tidak ditemukan."
    }

    $insertPos = $pos + $needle.Length

    $guard = @'

        abort_if(
            $role->name === 'super-admin'
            && ! auth()->user()->hasRole('super-admin'),
            404
        );
'@

    $content = $content.Insert(
        $insertPos,
        $guard
    )
}

Write-Utf8NoBom $path $content
Write-Host "[OK] Role super-admin isolation" -ForegroundColor Green

# ------------------------------------------------------------
# Account Recovery list: hide super-admin for non-super-admin.
# Direct actions are additionally blocked centrally in AccountSecurityService.
# ------------------------------------------------------------
$path = "app\Livewire\Security\AccountRecoveryIndex.php"
$content = Read-Utf8 $path

if ($content -notmatch "whereDoesntHave\(\s*'roles'") {
    $needle = "                'users' => User::query()"

    if (-not $content.Contains($needle)) {
        throw "Anchor AccountRecoveryIndex tidak ditemukan."
    }

    $replacement = @"
                'users' => User::query()
                    ->when(
                        ! auth()->user()->hasRole('super-admin'),
                        fn (`$query) => `$query->whereDoesntHave(
                            'roles',
                            fn (`$roleQuery) => `$roleQuery->where(
                                'name',
                                'super-admin'
                            )
                        )
                    )
"@

    $content = $content.Replace(
        $needle,
        $replacement.TrimEnd()
    )

    Write-Utf8NoBom $path $content
    Write-Host "[OK] Recovery list super-admin isolation" -ForegroundColor Green
}

# ------------------------------------------------------------
# Seeder: new staff role + personal permissions.
# ------------------------------------------------------------
$path = "database\seeders\RolePermissionSeeder.php"
$content = Read-Utf8 $path

if ($content -notmatch "'my-dashboard\.view'") {
    $needle = "            'dashboard.view',"

    if (-not $content.Contains($needle)) {
        throw "Anchor dashboard.view tidak ditemukan."
    }

    $content = $content.Replace(
        $needle,
        $needle + @"

            'my-dashboard.view',
            'my-letters.view',
            'my-reports.view',
"@
    )
}

if ($content -notmatch "^\s*'staff',\s*$") {
    $needle = "            'viewer',"

    if (-not $content.Contains($needle)) {
        throw "Anchor role viewer tidak ditemukan."
    }

    $content = $content.Replace(
        $needle,
        "$needle`r`n            'staff',"
    )
}

if ($content -notmatch "\$staff->syncPermissions") {
    $needle = @'
        $superAdmin->syncPermissions(
            Permission::where('guard_name', $guard)->get()
        );
'@

    if (-not $content.Contains($needle)) {
        throw "Anchor super-admin permission sync tidak ditemukan."
    }

    $block = @'

        $staff = Role::findByName('staff', $guard);

        $staff->syncPermissions([
            'my-dashboard.view',
            'my-letters.view',
            'my-reports.view',
        ]);
'@

    $content = $content.Replace(
        $needle,
        $needle + $block
    )
}

Write-Utf8NoBom $path $content
Write-Host "[OK] Role staff + personal permissions" -ForegroundColor Green

# ------------------------------------------------------------
# Routes: only add personal routes/imports.
# ------------------------------------------------------------
$path = "routes\web.php"
$content = Read-Utf8 $path

$imports = @(
    "use App\Livewire\MySpt\Index as MySptIndex;",
    "use App\Livewire\MySpt\Show as MySptShow;",
    "use App\Livewire\MyRecap\Index as MyRecapIndex;"
)

$routeImport = 'use Illuminate\Support\Facades\Route;'

foreach ($import in $imports) {
    if (-not $content.Contains($import)) {
        if (-not $content.Contains($routeImport)) {
            throw "Import Route tidak ditemukan."
        }

        $content = $content.Replace(
            $routeImport,
            $import + "`r`n" + $routeImport
        )
    }
}

if ($content -notmatch "name\('my-spt\.index'\)") {
    $groupEnd = $content.LastIndexOf("});")

    if ($groupEnd -lt 0) {
        throw "Akhir auth/verified route group tidak ditemukan."
    }

    $block = @'

    /*
    |--------------------------------------------------------------------------
    | Personal Staff Portal
    |--------------------------------------------------------------------------
    */

    Route::get('/my/spt', MySptIndex::class)
        ->name('my-spt.index');

    Route::get('/my/spt/{letter}', MySptShow::class)
        ->name('my-spt.show');

    Route::get('/my/recap', MyRecapIndex::class)
        ->name('my-recap.index');

'@

    $content = $content.Insert(
        $groupEnd,
        $block
    )
}

Write-Utf8NoBom $path $content
Write-Host "[OK] Personal portal routes" -ForegroundColor Green

# ------------------------------------------------------------
# SIDEBAR SAFE PATCH
# Preserve $groups, accordion, styling, responsive behaviour and CSS.
# ------------------------------------------------------------
$path = $sidebarPath
$content = Read-Utf8 $path

$dashboardOld = @'
        @can('dashboard.view')
            <x-app.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-app.nav-link>
        @endcan
'@

$dashboardNew = @'
        @canany(['dashboard.view', 'my-dashboard.view'])
            <x-app.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-app.nav-link>
        @endcanany
'@

if ($content.Contains($dashboardOld)) {
    $content = $content.Replace(
        $dashboardOld,
        $dashboardNew
    )
}
elseif ($content -notmatch "my-dashboard\.view") {
    throw "Blok Dashboard sidebar terbaru tidak dikenali. Installer dihentikan agar desain tidak rusak."
}

if ($content -notmatch "route\('my-spt\.index'\)") {
    $anchor = "        @foreach(['work' => 'Ruang Kerja', 'manage' => 'Pengelolaan'] as `$section => `$heading)"

    if (-not $content.Contains($anchor)) {
        throw "Anchor section sidebar terbaru tidak ditemukan. Sidebar backup tersimpan dan desain tidak ditimpa."
    }

    $personal = @'

        @if(
            (auth()->user()->can('my-letters.view') || auth()->user()->can('my-reports.view'))
            && ! auth()->user()->can('letters.view')
            && ! auth()->user()->can('reports.view')
        )
            <div class="sidebar-section">
                <p class="sidebar-section-label">Administrasi Saya</p>

                @can('my-letters.view')
                    <x-app.nav-link
                        :href="route('my-spt.index')"
                        :active="request()->routeIs('my-spt.*')"
                        icon="document"
                    >
                        SPT Saya
                    </x-app.nav-link>
                @endcan

                @can('my-reports.view')
                    <x-app.nav-link
                        :href="route('my-recap.index')"
                        :active="request()->routeIs('my-recap.*')"
                        icon="chart"
                    >
                        Rekap Saya
                    </x-app.nav-link>
                @endcan
            </div>
        @endif

'@

    $content = $content.Replace(
        $anchor,
        $personal + $anchor
    )
}

Write-Utf8NoBom $path $content
Write-Host "[OK] Sidebar dipatch tanpa mengganti desain terbaru" -ForegroundColor Green

Write-Host ""
Write-Host "[OK] Phase 12.1 v2 selesai." -ForegroundColor Green
Write-Host ""
Write-Host "File sidebar asli tersimpan di:" -ForegroundColor Cyan
Write-Host "storage\app\phase-backups\sidebar-before-phase12-1-v2.blade.php"
Write-Host ""
Write-Host "Lanjutkan:" -ForegroundColor Cyan
Write-Host "php -l app\Models\User.php"
Write-Host "php -l app\Support\PersonalLetterAccess.php"
Write-Host "php -l app\Livewire\Dashboard.php"
Write-Host "php -l app\Livewire\MySpt\Index.php"
Write-Host "php -l app\Livewire\MySpt\Show.php"
Write-Host "php -l app\Livewire\MyRecap\Index.php"
Write-Host "php artisan db:seed --class=RolePermissionSeeder"
Write-Host "php artisan permission:cache-reset"
Write-Host "php artisan view:clear"
Write-Host "php artisan test --compact --filter=PhaseTwelveOneV2Test"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
