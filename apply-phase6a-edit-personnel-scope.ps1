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
# 1. Backend Edit.php memahami personnel_scope
# ============================================================
$path = "app\Livewire\Letters\Edit.php"

Replace-Exact `
    -Path $path `
    -Label "Tambah state personnel_scope pada Edit SPT" `
    -Old @'
    public string $record_type = 'normal';

    public array $personnel_ids = [];
'@ `
    -New @'
    public string $record_type = 'normal';

    public string $personnel_scope = Letter::PERSONNEL_SCOPE_SELECTED;

    public array $personnel_ids = [];
'@

Replace-Exact `
    -Path $path `
    -Label "Muat personnel_scope dari SPT" `
    -Old @'
        $this->record_type = $letter->record_type?->value ?? LetterRecordType::Normal->value;

        $this->personnel_ids = $letter
'@ `
    -New @'
        $this->record_type = $letter->record_type?->value ?? LetterRecordType::Normal->value;
        $this->personnel_scope = $letter->personnel_scope
            ?? Letter::PERSONNEL_SCOPE_SELECTED;

        $this->personnel_ids = $letter
'@

Replace-Exact `
    -Path $path `
    -Label "Tambah aksi scope dan helper pemilihan pada Edit SPT" `
    -Old @'
    public function save(LetterService $service)
    {
'@ `
    -New @'
    public function useSelectedPersonnelScope(): void
    {
        Gate::authorize('letters.update');

        $this->personnel_scope = Letter::PERSONNEL_SCOPE_SELECTED;

        $this->resetValidation([
            'personnel_scope',
            'personnel_ids',
        ]);
    }

    public function useAllPersonnelScope(): void
    {
        Gate::authorize('letters.update');

        $this->personnel_scope = Letter::PERSONNEL_SCOPE_ALL;
        $this->personnel_ids = [];

        $this->resetValidation([
            'personnel_scope',
            'personnel_ids',
        ]);
    }

    public function selectAllPersonnel(): void
    {
        Gate::authorize('letters.update');

        $this->personnel_ids = $this->personnelQuery()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->resetValidation('personnel_ids');
    }

    public function selectVisiblePersonnel(): void
    {
        Gate::authorize('letters.update');

        $visibleIds = $this->personnelQuery()
            ->limit(100)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->personnel_ids = array_values(array_unique([
            ...array_map('intval', $this->personnel_ids),
            ...$visibleIds,
        ]));

        $this->resetValidation('personnel_ids');
    }

    public function clearAllPersonnel(): void
    {
        Gate::authorize('letters.update');

        $this->personnel_ids = [];

        $this->resetValidation('personnel_ids');
    }

    public function save(LetterService $service)
    {
'@

Replace-Exact `
    -Path $path `
    -Label "Validasi personnel_scope pada Edit SPT" `
    -Old @'
            'record_type' => ['required', 'in:normal,attendance_correction'],
            'personnel_ids' => ['required', 'array', 'min:1'],
            'personnel_ids.*' => [
'@ `
    -New @'
            'record_type' => ['required', 'in:normal,attendance_correction'],
            'personnel_scope' => ['required', 'in:selected,all'],
            'personnel_ids' => $this->personnel_scope === Letter::PERSONNEL_SCOPE_SELECTED
                ? ['required', 'array', 'min:1']
                : ['array', 'max:0'],
            'personnel_ids.*' => [
'@

Replace-Exact `
    -Path $path `
    -Label "Scope all tidak mengirim pivot personil saat Edit" `
    -Old @'
        $service->updateSpt(
            $this->letter,
            $data,
            Auth::id()
        );
'@ `
    -New @'
        if (
            $data['personnel_scope']
            === Letter::PERSONNEL_SCOPE_ALL
        ) {
            $data['personnel_ids'] = [];
        }

        $service->updateSpt(
            $this->letter,
            $data,
            Auth::id()
        );
'@

# Refactor render personnel query ke helper agar konsisten dengan select all.
Replace-Exact `
    -Path $path `
    -Label "Tambah helper personnelQuery pada Edit SPT" `
    -Old @'
    public function render()
    {
        return view('livewire.letters.edit', [
            'activityTypes' => ActivityType::query()
                ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $this->letter->activity_type_id))
                ->orderBy('name')
                ->get(),
            'personnels' => Personnel::query()
                ->with('unit')
                ->where(function ($query) {
                    $query->where('is_active', true);
                    if ($this->letter->source === 'import') {
                        $query->orWhereIn('id', $this->letter->personnels()->select('personnels.id'));
                    }
                })
                ->when(
                    filled($this->personnelSearch),
                    fn ($query) => $query->where(
                        'name',
                        'ilike',
                        '%'.trim($this->personnelSearch).'%'
                    )
                )
                ->orderBy('name')
                ->limit(100)
                ->get(),
        ]);
    }
'@ `
    -New @'
    private function personnelQuery()
    {
        return Personnel::query()
            ->with('unit')
            ->where(function ($query) {
                $query->where('is_active', true);

                if ($this->letter->source === 'import') {
                    $query->orWhereIn(
                        'id',
                        $this->letter
                            ->personnels()
                            ->select('personnels.id')
                    );
                }
            })
            ->when(
                filled($this->personnelSearch),
                fn ($query) => $query->where(
                    'name',
                    'ilike',
                    '%'.trim($this->personnelSearch).'%'
                )
            )
            ->orderBy('name');
    }

    public function render()
    {
        return view('livewire.letters.edit', [
            'activityTypes' => ActivityType::query()
                ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $this->letter->activity_type_id))
                ->orderBy('name')
                ->get(),

            'personnels' => $this->personnelQuery()
                ->limit(100)
                ->get(),

            'totalSelectablePersonnel' => $this->personnelQuery()
                ->count(),
        ]);
    }
'@

# ============================================================
# 2. UI Edit SPT — samakan konsep dengan form Tambah SPT
# ============================================================
$path = "resources\views\livewire\letters\edit.blade.php"

$old = @'
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-blue-900">
                Personil
            </h2>

            <input
                type="search"
                wire:model.live.debounce.300ms="personnelSearch"
                placeholder="Cari personil..."
                class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2"
            >

            <div class="mt-4 max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200">
                @foreach ($personnels as $personnel)
                    <label class="flex items-center gap-3 p-3">
                        <input
                            type="checkbox"
                            wire:model="personnel_ids"
                            value="{{ $personnel->id }}"
                        >

                        <span class="text-sm">
                            {{ $personnel->name }}
                        </span>
                    </label>
                @endforeach
            </div>

            @error('personnel_ids')
                <p class="mt-2 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </section>
'@

$new = @'
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-blue-900">
                        Personil
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        @if($personnel_scope === 'all')
                            SPT berlaku untuk seluruh pegawai. Tidak dibuat relasi personil individual.
                        @else
                            {{ count($personnel_ids) }} dari {{ $totalSelectablePersonnel }} personil tersedia dipilih.
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="selectAllPersonnel"
                        wire:loading.attr="disabled"
                        wire:target="selectAllPersonnel"
                        @disabled($personnel_scope === 'all')
                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="selectAllPersonnel">
                            Pilih Semua Personil Tersedia
                        </span>
                        <span wire:loading wire:target="selectAllPersonnel">
                            Memilih...
                        </span>
                    </button>

                    @if(filled($personnelSearch))
                        <button
                            type="button"
                            wire:click="selectVisiblePersonnel"
                            wire:loading.attr="disabled"
                            wire:target="selectVisiblePersonnel"
                            @disabled($personnel_scope === 'all')
                            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
                        >
                            Pilih Hasil Pencarian
                        </button>
                    @endif

                    @if(count($personnel_ids) > 0)
                        <button
                            type="button"
                            wire:click="clearAllPersonnel"
                            @disabled($personnel_scope === 'all')
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 disabled:opacity-60"
                        >
                            Batalkan Semua
                        </button>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="useSelectedPersonnelScope"
                    class="rounded-lg border px-3 py-2 text-xs font-semibold transition {{ $personnel_scope === 'selected' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    Personil Tertentu
                </button>

                <button
                    type="button"
                    wire:click="useAllPersonnelScope"
                    class="rounded-lg border px-3 py-2 text-xs font-semibold transition {{ $personnel_scope === 'all' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    Seluruh Pegawai
                </button>
            </div>

            @if($personnel_scope === 'all')
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                    SPT akan dicatat dengan cakupan <strong>Seluruh Pegawai</strong>.
                    Relasi personil individual akan dikosongkan sehingga Rekap Personil tetap akurat.
                    Daftar di bawah tetap ditampilkan sebagai referensi.
                </div>
            @endif

            @if($letter->source === 'import')
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                    Personil lama yang sudah terkait dengan SPT hasil import tetap dapat dipertahankan walaupun saat ini berstatus nonaktif.
                </div>
            @endif

            @error('personnel_scope')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror

            <input
                type="search"
                wire:model.live.debounce.300ms="personnelSearch"
                @disabled($personnel_scope === 'all')
                placeholder="Cari personil..."
                class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2"
            >

            <div class="mt-4 max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200">
                @forelse($personnels as $personnel)
                    <label class="flex cursor-pointer items-center gap-3 p-3 hover:bg-blue-50">
                        <input
                            type="checkbox"
                            wire:model="personnel_ids"
                            @disabled($personnel_scope === 'all')
                            value="{{ $personnel->id }}"
                            class="rounded border-slate-300 text-blue-700"
                        >

                        <span>
                            <span class="block text-sm font-medium">
                                {{ $personnel->name }}
                            </span>

                            <span class="text-xs text-slate-500">
                                {{ $personnel->nip ?: 'Tanpa NIP' }}
                                @if($personnel->position)
                                    · {{ $personnel->position }}
                                @endif
                                @if(! $personnel->is_active)
                                    · Tidak Aktif
                                @endif
                            </span>
                        </span>
                    </label>
                @empty
                    <div class="p-6 text-center text-sm text-slate-500">
                        Personil tidak ditemukan.
                    </div>
                @endforelse
            </div>

            @error('personnel_ids')
                <p class="mt-2 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </section>
'@

Replace-Exact `
    -Path $path `
    -Old $old `
    -New $new `
    -Label "Samakan Personil Edit SPT dengan scope Personil Tertentu/Seluruh Pegawai"

# ============================================================
# 3. Integration tests
# ============================================================
$sourceRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$relative = "tests\Feature\SptEditPersonnelScopeTest.php"
$source = Join-Path $sourceRoot $relative
$destination = Join-Path (Get-Location) $relative
$parent = Split-Path $destination -Parent

if (-not (Test-Path $source)) {
    throw "File test patch tidak ditemukan: $source"
}

if (-not (Test-Path $parent)) {
    New-Item -ItemType Directory -Force -Path $parent | Out-Null
}

$testContent = [System.IO.File]::ReadAllText($source)
[System.IO.File]::WriteAllText(
    $destination,
    $testContent,
    $utf8NoBom
)

Write-Host "[OK] Tambah SptEditPersonnelScopeTest" -ForegroundColor Green

Write-Host ""
Write-Host "Phase 6A — Edit SPT Personnel Scope terpasang." -ForegroundColor Cyan
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php -l app\Livewire\Letters\Edit.php"
Write-Host "php -l tests\Feature\SptEditPersonnelScopeTest.php"
Write-Host "php artisan view:clear"
Write-Host "php artisan test --compact --filter=SptEditPersonnelScopeTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
