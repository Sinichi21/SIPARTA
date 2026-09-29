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

    if (-not (Test-Path $Path)) {
        throw "File tidak ditemukan: $Path"
    }

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
# Backend: state + method + query
# ============================================================
$path = "app\Livewire\PersonnelRecap\Index.php"

Replace-Exact -Path $path -Label "Tambah state drawer SPT Seluruh Pegawai" -Old @'
    public bool $showAllHistory = false;
'@ -New @'
    public bool $showAllHistory = false;
    public bool $showAllPersonnelSpt = false;
'@

Replace-Exact -Path $path -Label "Tambah aksi buka/tutup drawer SPT Seluruh Pegawai" -Old @'
    public function toggleAllHistory(): void
    {
        if (! $this->selectedPersonnelId) {
            return;
        }

        $this->showAllHistory = ! $this->showAllHistory;
    }
'@ -New @'
    public function toggleAllHistory(): void
    {
        if (! $this->selectedPersonnelId) {
            return;
        }

        $this->showAllHistory = ! $this->showAllHistory;
    }

    public function openAllPersonnelSpt(): void
    {
        $this->showAllPersonnelSpt = true;
    }

    public function closeAllPersonnelSpt(): void
    {
        $this->showAllPersonnelSpt = false;
    }
'@

Replace-Exact -Path $path -Label "Ambil daftar SPT Seluruh Pegawai sesuai filter" -Old @'
        /*
         * SPT bulan ini memang selalu berarti bulan berjalan.
'@ -New @'
        $allPersonnelSptRows = collect();

        if ($this->showAllPersonnelSpt) {
            $allPersonnelSptRows = Letter::query()
                ->whereHas(
                    'letterType',
                    fn (Builder $q) => $q->where('code', 'SPT')
                )
                ->where(
                    'personnel_scope',
                    Letter::PERSONNEL_SCOPE_ALL
                )
                ->when(
                    $this->recordType !== 'all',
                    fn (Builder $q) => $q->where(
                        'record_type',
                        $this->recordType
                    )
                )
                ->when(
                    $this->year !== '',
                    fn (Builder $q) => $q->whereYear(
                        'letter_date',
                        (int) $this->year
                    )
                )
                ->when(
                    $this->activityTypeId !== '',
                    fn (Builder $q) => $q->where(
                        'activity_type_id',
                        (int) $this->activityTypeId
                    )
                )
                ->with('activityType')
                ->orderByDesc('letter_date')
                ->orderByDesc('id')
                ->get();
        }

        /*
         * SPT bulan ini memang selalu berarti bulan berjalan.
'@

Replace-Exact -Path $path -Label "Kirim daftar SPT Seluruh Pegawai ke view" -Old @'
                'allPersonnelSpt' => $allPersonnelSpt,
                'monthAssignments' => $monthAssignments,
'@ -New @'
                'allPersonnelSpt' => $allPersonnelSpt,
                'allPersonnelSptRows' => $allPersonnelSptRows,
                'monthAssignments' => $monthAssignments,
'@

# ============================================================
# UI: banner + button
# ============================================================
$path = "resources\views\livewire\personnel-recap\index.blade.php"

Replace-Exact -Path $path -Label "Tambah tombol Lihat SPT pada info Seluruh Pegawai" -Old @'
    @if(($allPersonnelSpt ?? 0) > 0)
        <div class="rounded-2xl border border-blue-100 bg-blue-50/70 px-4 py-3 text-sm text-blue-900">
            <span class="font-semibold">
                {{ number_format($allPersonnelSpt) }} SPT Seluruh Pegawai
            </span>
            <span class="text-blue-700">
                pada filter aktif. SPT ini tetap tercatat dalam rekap, tetapi tidak ditambahkan ke jumlah SPT personil individual.
            </span>
        </div>
    @endif
'@ -New @'
    @if(($allPersonnelSpt ?? 0) > 0)
        <div class="flex flex-col gap-3 rounded-2xl border border-blue-100 bg-blue-50/70 px-4 py-3 text-sm text-blue-900 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="font-semibold">
                    {{ number_format($allPersonnelSpt) }} SPT Seluruh Pegawai
                </span>
                <span class="text-blue-700">
                    pada filter aktif. SPT ini tetap tercatat dalam rekap, tetapi tidak ditambahkan ke jumlah SPT personil individual.
                </span>
            </div>

            <button
                type="button"
                wire:click="openAllPersonnelSpt"
                wire:loading.attr="disabled"
                wire:target="openAllPersonnelSpt"
                class="shrink-0 rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="openAllPersonnelSpt">
                    Lihat SPT
                </span>
                <span wire:loading wire:target="openAllPersonnelSpt">
                    Membuka...
                </span>
            </button>
        </div>
    @endif
'@

# Add drawer before existing selectedPersonnel drawer include.
$content = Read-Utf8 $path

$marker = @'
    @if($selectedPersonnel)
        @include('livewire.personnel-recap.detail')
    @endif
'@

$drawer = @'
    @if($showAllPersonnelSpt)
        <x-app.detail-drawer title="SPT Seluruh Pegawai">
            <section class="drawer-section">
                <div class="flex items-start gap-3">
                    <span class="detail-icon">
                        <x-app.icon name="users" />
                    </span>

                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            SPT Seluruh Pegawai
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Daftar ini mengikuti filter tahun, jenis record, dan jenis kegiatan pada Rekap Personil.
                            Data ini tidak ditambahkan ke jumlah SPT personil individual.
                        </p>
                    </div>
                </div>
            </section>

            <section class="drawer-section">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="drawer-section-title">
                        Daftar SPT
                    </h3>

                    <span class="count-badge">
                        {{ number_format($allPersonnelSptRows->count()) }} data
                    </span>
                </div>

                <div class="detail-table-wrap">
                    <table class="detail-table history-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nomor SPT</th>
                                <th>Tanggal</th>
                                <th>Kegiatan</th>
                                <th>Lokasi</th>
                                <th>Jenis</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($allPersonnelSptRows as $letter)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        <a
                                            href="{{ route('letters.show', $letter) }}"
                                            wire:navigate
                                            class="font-semibold text-blue-700 hover:underline"
                                        >
                                            {{ $letter->number ?: 'Draft #'.$letter->id }}
                                        </a>
                                    </td>

                                    <td>
                                        {{ $letter->letter_date
                                            ?->translatedFormat('d M Y')
                                            ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $letter->subject
                                            ?: $letter->activityType?->name
                                            ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $letter->location ?: '-' }}
                                    </td>

                                    <td>
                                        <span
                                            class="status-badge
                                            {{ $letter->record_type?->value === 'attendance_correction'
                                                ? 'status-warning'
                                                : 'status-success' }}"
                                        >
                                            {{ $letter->record_type?->label()
                                                ?? 'SPT Normal' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="detail-empty">
                                        Tidak ada SPT Seluruh Pegawai pada filter aktif.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <x-slot:footer>
                <button
                    type="button"
                    wire:click="closeAllPersonnelSpt"
                    class="detail-button detail-button-outline"
                >
                    Tutup
                </button>
            </x-slot:footer>
        </x-app.detail-drawer>
    @endif

    @if($selectedPersonnel)
        @include('livewire.personnel-recap.detail')
    @endif
'@

if ($content.Contains('title="SPT Seluruh Pegawai"')) {
    Write-Host "[SKIP] Drawer SPT Seluruh Pegawai sudah terpasang" -ForegroundColor Yellow
} else {
    if (-not $content.Contains($marker)) {
        throw "Tidak menemukan marker drawer detail personil di index.blade.php"
    }

    $content = $content.Replace($marker, $drawer)
    Write-Utf8NoBom $path $content
    Write-Host "[OK] Tambah drawer daftar SPT Seluruh Pegawai" -ForegroundColor Green
}

Write-Host ""
Write-Host "Fitur tombol Lihat SPT berhasil diterapkan." -ForegroundColor Cyan
Write-Host "Lanjutkan dengan:" -ForegroundColor Cyan
Write-Host "php -l app\Livewire\PersonnelRecap\Index.php"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
