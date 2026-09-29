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

function Replace-Exact {
    param(
        [string]$Path,
        [string]$Old,
        [string]$New,
        [string]$Label
    )

    $content = Read-Utf8 $Path

    if ($content.Contains($New)) {
        Write-Host "[SKIP] $Label sudah terpasang" -ForegroundColor Yellow
        return
    }

    if (-not $content.Contains($Old)) {
        throw "Tidak menemukan blok untuk: $Label`nFile: $Path"
    }

    $content = $content.Replace($Old, $New)
    Write-Utf8NoBom $Path $content
    Write-Host "[OK] $Label" -ForegroundColor Green
}

# ============================================================
# 1. Backend export CSV Rekap SPT
# ============================================================
$path = "app\Livewire\SptRecap\Index.php"

$old = @'
    public function render()
    {
'@

$new = @'
    public function exportCsv()
    {
        Gate::authorize('reports.view');

        $letters = $this->filteredQuery()
            ->with([
                'activityType',
                'personnels.unit',
            ])
            ->orderByDesc('letter_date')
            ->orderByDesc('id')
            ->get();

        $yearLabel = $this->year !== ''
            ? $this->year
            : 'semua-tahun';

        $filename = sprintf(
            'rekap-spt-%s-%s.csv',
            $yearLabel,
            now()->format('Ymd-His')
        );

        $csvSafe = static function ($value): string {
            $value = (string) ($value ?? '');

            if (
                $value !== ''
                && preg_match('/^[=+\-@\t\r]/u', $value)
            ) {
                return "'".$value;
            }

            return $value;
        };

        return response()->streamDownload(
            function () use ($letters, $csvSafe) {
                $handle = fopen('php://output', 'w');

                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [
                    'Nomor SPT',
                    'Tanggal SPT',
                    'Tanggal Mulai',
                    'Tanggal Selesai',
                    'Kegiatan',
                    'Jenis Kegiatan',
                    'Lokasi',
                    'Cakupan Personil',
                    'Personil',
                    'Unit/Tim',
                    'Jenis Record',
                    'Status',
                    'Sumber',
                ]);

                foreach ($letters as $letter) {
                    $allPersonnel = $letter->assignsAllPersonnel();

                    $personnelNames = $allPersonnel
                        ? 'Seluruh Pegawai'
                        : $letter->personnels
                            ->pluck('name')
                            ->filter()
                            ->implode('; ');

                    $unitNames = $allPersonnel
                        ? ''
                        : $letter->personnels
                            ->pluck('unit.name')
                            ->filter()
                            ->unique()
                            ->values()
                            ->implode('; ');

                    fputcsv($handle, [
                        $csvSafe($letter->number ?? ''),
                        $csvSafe(
                            $letter->letter_date
                                ?->format('Y-m-d') ?? ''
                        ),
                        $csvSafe(
                            $letter->start_date
                                ?->format('Y-m-d') ?? ''
                        ),
                        $csvSafe(
                            $letter->end_date
                                ?->format('Y-m-d') ?? ''
                        ),
                        $csvSafe(
                            $letter->subject
                                ?: $letter->activityType?->name
                                ?: ''
                        ),
                        $csvSafe(
                            $letter->activityType?->name ?? ''
                        ),
                        $csvSafe($letter->location ?? ''),
                        $csvSafe(
                            $allPersonnel
                                ? 'Seluruh Pegawai'
                                : 'Personil Tertentu'
                        ),
                        $csvSafe($personnelNames),
                        $csvSafe($unitNames),
                        $csvSafe(
                            $letter->record_type?->label()
                                ?? 'SPT Normal'
                        ),
                        $csvSafe(
                            $letter->status?->label()
                                ?? ''
                        ),
                        $csvSafe(
                            $letter->source === 'import'
                                ? 'Import Arsip'
                                : 'Dibuat dari Sistem'
                        ),
                    ]);
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }

    public function render()
    {
'@

Replace-Exact `
    -Path $path `
    -Old $old `
    -New $new `
    -Label "Tambah export CSV Rekap SPT"

# ============================================================
# 2. Tombol Export CSV di header, tanpa mengubah layout utama
# ============================================================
$path = "resources\views\livewire\spt-recap\index.blade.php"

$old = @'
        <div class="flex flex-wrap gap-2">
            @can('letters.import')
'@

$new = @'
        <div class="flex flex-wrap gap-2">
            @can('reports.view')
                <button
                    type="button"
                    wire:click="exportCsv"
                    wire:loading.attr="disabled"
                    wire:target="exportCsv"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
                    title="Export seluruh Rekap SPT sesuai filter aktif"
                >
                    <x-app.icon name="download" class="size-4" />
                    <span wire:loading.remove wire:target="exportCsv">
                        Export CSV
                    </span>
                    <span wire:loading wire:target="exportCsv">
                        Menyiapkan...
                    </span>
                </button>
            @endcan

            @can('letters.import')
'@

Replace-Exact `
    -Path $path `
    -Old $old `
    -New $new `
    -Label "Tambah tombol Export CSV pada Rekap SPT"

# ============================================================
# 3. Copy integration test
# ============================================================
$sourceRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$relative = "tests\Feature\SptRecapExportTest.php"
$source = Join-Path $sourceRoot $relative
$destination = Join-Path (Get-Location) $relative

if (-not (Test-Path $source)) {
    throw "File test patch tidak ditemukan: $source"
}

$parent = Split-Path $destination -Parent

if (-not (Test-Path $parent)) {
    New-Item -ItemType Directory -Force -Path $parent | Out-Null
}

$testContent = [System.IO.File]::ReadAllText($source)
[System.IO.File]::WriteAllText(
    $destination,
    $testContent,
    $utf8NoBom
)

Write-Host "[OK] Tambah SptRecapExportTest" -ForegroundColor Green

Write-Host ""
Write-Host "Phase 5 Rekap SPT Export selesai dipasang." -ForegroundColor Cyan
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php -l app\Livewire\SptRecap\Index.php"
Write-Host "php -l tests\Feature\SptRecapExportTest.php"
Write-Host "php artisan view:clear"
Write-Host "php artisan test --compact --filter=SptRecapExportTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
