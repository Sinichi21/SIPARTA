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

$path = "resources\views\components\app\sidebar.blade.php"

if (-not (Test-Path $path)) {
    throw "sidebar.blade.php tidak ditemukan."
}

$content = Read-Utf8 $path

if (
    ($content -notmatch '\$groups\s*=\s*collect') -or
    ($content -notmatch '<x-app\.sidebar-group')
) {
    throw "Struktur sidebar terbaru tidak dikenali. Tidak ada perubahan dilakukan."
}

# Backup current sidebar before touching it.
$backupDir = "storage\app\phase-backups"
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
Copy-Item `
    $path `
    "$backupDir\sidebar-before-phase12-1-v2-2-$stamp.blade.php" `
    -Force

Write-Host "[OK] Backup sidebar dibuat" -ForegroundColor Green

# ------------------------------------------------------------
# 1. Dashboard permission
# Robust regex: independent of indentation/newline style.
# ------------------------------------------------------------
if ($content -notmatch "my-dashboard\.view") {
    $pattern = '(?ms)@can\(''dashboard\.view''\)\s*' +
        '(<x-app\.nav-link\s+:href="route\(''dashboard''\)"\s+' +
        ':active="request\(\)->routeIs\(''dashboard''\)"\s+' +
        'icon="home">Dashboard</x-app\.nav-link>)\s*@endcan'

    $replacement = @'
@canany(['dashboard.view', 'my-dashboard.view'])
            $1
        @endcanany
'@

    $updated = [regex]::Replace(
        $content,
        $pattern,
        $replacement,
        1
    )

    if ($updated -eq $content) {
        throw "Blok Dashboard tidak dapat dipatch secara aman. Backup sudah dibuat; sidebar asli tidak ditimpa."
    }

    $content = $updated
    Write-Host "[OK] Dashboard mendukung my-dashboard.view" -ForegroundColor Green
}
else {
    Write-Host "[SKIP] my-dashboard.view sudah ada di sidebar" -ForegroundColor Yellow
}

# ------------------------------------------------------------
# 2. Personal staff section
# Insert immediately before the main section foreach.
# ------------------------------------------------------------
if ($content -notmatch "route\('my-spt\.index'\)") {
    $anchorPattern = '(?m)^\s*@foreach\(\[''work''\s*=>\s*''Ruang Kerja'',\s*''manage''\s*=>\s*''Pengelolaan''\]\s+as\s+\$section\s*=>\s*\$heading\)'

    $match = [regex]::Match(
        $content,
        $anchorPattern
    )

    if (-not $match.Success) {
        throw "Anchor section Ruang Kerja/Pengelolaan tidak ditemukan. Backup sudah dibuat; desain sidebar tidak diganti."
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

    $content = $content.Insert(
        $match.Index,
        $personal
    )

    Write-Host "[OK] Administrasi Saya ditambahkan" -ForegroundColor Green
}
else {
    Write-Host "[SKIP] Administrasi Saya sudah ada" -ForegroundColor Yellow
}

Write-Utf8NoBom $path $content

Write-Host ""
Write-Host "[OK] Repair sidebar Phase 12.1 v2.2 selesai." -ForegroundColor Green
Write-Host ""
Write-Host "Selanjutnya jalankan:" -ForegroundColor Cyan
Write-Host "php artisan view:clear"
Write-Host "php artisan permission:cache-reset"
Write-Host "php artisan test --compact --filter=PhaseTwelveOneV2Test"
Write-Host "npm run build"
