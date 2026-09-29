$ErrorActionPreference = "Stop"

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Write-NewUtf8NoBom {
    param([string]$Path, [string]$Content)

    $parent = Split-Path $Path -Parent
    if ($parent -and -not (Test-Path $parent)) {
        New-Item -ItemType Directory -Force -Path $parent | Out-Null
    }

    [System.IO.File]::WriteAllText(
        (Join-Path (Get-Location) $Path),
        $Content,
        $utf8NoBom
    )
}

function Read-Utf8 {
    param([string]$Path)
    return [System.IO.File]::ReadAllText((Resolve-Path $Path))
}

function Write-Utf8NoBom {
    param([string]$Path, [string]$Content)
    [System.IO.File]::WriteAllText((Resolve-Path $Path), $Content, $utf8NoBom)
}

# Copy new files bundled with this patch.
$sourceRoot = Split-Path -Parent $MyInvocation.MyCommand.Path

$copyFiles = @(
    "app\Services\SptActivityTypeMatcher.php",
    "app\Console\Commands\MapHistoricalSptActivityTypes.php",
    "tests\Unit\SptActivityTypeMatcherTest.php"
)

foreach ($relative in $copyFiles) {
    $source = Join-Path $sourceRoot $relative

    if (-not (Test-Path $source)) {
        throw "File patch tidak ditemukan: $source"
    }

    $content = [System.IO.File]::ReadAllText($source)
    Write-NewUtf8NoBom -Path $relative -Content $content
    Write-Host "[OK] $relative" -ForegroundColor Green
}

# Patch SptImportService so future imports use the same matcher.
$path = "app\Services\SptImportService.php"
$content = Read-Utf8 $path

if (-not $content.Contains("private readonly SptActivityTypeMatcher `$activityMatcher")) {
    $needle = @'
class SptImportService
{
'@
    $replacement = @'
class SptImportService
{
    public function __construct(
        private readonly SptActivityTypeMatcher $activityMatcher
    ) {}
'@

    if (-not $content.Contains($needle)) {
        throw "Tidak menemukan deklarasi class SptImportService."
    }

    $content = $content.Replace($needle, $replacement)
    Write-Host "[OK] Tambah matcher ke SptImportService" -ForegroundColor Green
} else {
    Write-Host "[SKIP] Matcher sudah ada di SptImportService" -ForegroundColor Yellow
}

$old = @'
                    $activityTypeId = null;

                    if (filled($activityText)) {
                        $activityTypeId = ActivityType::query()
                            ->whereRaw('LOWER(name) = ?', [Str::lower(trim($activityText))])
                            ->value('id');
                    }
'@

$new = @'
                    $activityTypeId = $this->activityMatcher
                        ->matchId($activityText ?: $subject);
'@

if ($content.Contains($old)) {
    $content = $content.Replace($old, $new)
    Write-Host "[OK] Import baru memakai historical activity matcher" -ForegroundColor Green
} elseif ($content.Contains('$this->activityMatcher')) {
    Write-Host "[SKIP] Mapping import sudah memakai matcher" -ForegroundColor Yellow
} else {
    throw "Tidak menemukan blok mapping activity type pada SptImportService."
}

Write-Utf8NoBom $path $content

# Remove unused ActivityType import only when no longer referenced.
$content = Read-Utf8 $path
if (
    $content.Contains("use App\Models\ActivityType;") -and
    -not ([regex]::IsMatch($content, '\bActivityType::'))
) {
    $content = $content.Replace("use App\Models\ActivityType;`r`n", "")
    $content = $content.Replace("use App\Models\ActivityType;`n", "")
    Write-Utf8NoBom $path $content
    Write-Host "[OK] Hapus import ActivityType yang tidak lagi dipakai" -ForegroundColor Green
}

Write-Host ""
Write-Host "Phase 3 Activity Type Mapping terpasang." -ForegroundColor Cyan
Write-Host "Preview dulu:" -ForegroundColor Cyan
Write-Host "php artisan spt:map-activity-types"
Write-Host ""
Write-Host "JANGAN gunakan --apply sebelum hasil preview diperiksa." -ForegroundColor Yellow
