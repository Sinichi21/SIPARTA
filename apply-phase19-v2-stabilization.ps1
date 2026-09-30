$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Read-Utf8([string]$Path) {
    return [System.IO.File]::ReadAllText((Resolve-Path $Path))
}

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    [System.IO.File]::WriteAllText((Resolve-Path $Path), $Content, $utf8NoBom)
}

function Replace-Required(
    [string]$Path,
    [string]$Old,
    [string]$New,
    [string]$Label
) {
    $content = Read-Utf8 $Path

    if (-not $content.Contains($Old)) {
        throw "Patch gagal [$Label]. Anchor tidak ditemukan pada $Path"
    }

    $content = $content.Replace($Old, $New)
    Write-Utf8NoBom $Path $content
    Write-Host "[OK] $Label" -ForegroundColor Green
}

$required = @(
    "app\Models\Letter.php",
    "app\Livewire\Letters\Show.php",
    "resources\views\livewire\letters\show.blade.php",
    "app\Livewire\OutgoingLetters\Create.php",
    "app\Livewire\OutgoingLetters\Show.php",
    "resources\views\livewire\outgoing-letters\show.blade.php",
    "app\Livewire\IncomingLetters\Show.php",
    "resources\views\livewire\incoming-letters\show.blade.php"
)

foreach ($file in $required) {
    if (-not (Test-Path $file)) {
        throw "Prasyarat Phase 19 v2 tidak ditemukan: $file"
    }
}

# Guard against applying to an older baseline.
$create = Read-Utf8 "app\Livewire\OutgoingLetters\Create.php"
if (
    ($create -notmatch 'source_spt_id') -or
    ($create -notmatch 'manualPlaceholderNames') -or
    ($create -notmatch 'validateManualCandidate')
) {
    throw "Baseline terbaru belum terdeteksi. Pastikan source sudah sama dengan commit terbaru sebelum Phase 19 v2."
}

$show = Read-Utf8 "resources\views\livewire\outgoing-letters\show.blade.php"
if ($show -notmatch 'Mode Penomoran') {
    throw "Tampilan Surat Keluar terbaru belum terdeteksi. Patch dihentikan agar UI tidak tertimpa."
}

$backupDir = "storage\app\phase-backups\phase19-v2"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

foreach ($file in $required) {
    $safe = $file -replace '[\\/:*?"<>|]', '_'
    Copy-Item $file "$backupDir\$stamp-$safe" -Force
}

# -------------------------------------------------------------------------
# 1. Letter model: relation to generated outgoing letter.
# -------------------------------------------------------------------------
$path = "app\Models\Letter.php"
$content = Read-Utf8 $path

if ($content -notmatch 'function outgoingLetter') {
    $anchor = @'
    public function documentSnapshot(): HasOne
'@

    $relationship = @'
    public function outgoingLetter(): HasOne
    {
        return $this->hasOne(
            OutgoingLetter::class,
            'source_spt_id'
        );
    }

'@

    if (-not $content.Contains($anchor)) {
        throw "Anchor documentSnapshot() tidak ditemukan."
    }

    $content = $content.Replace(
        $anchor,
        ($relationship + $anchor)
    )

    Write-Utf8NoBom $path $content
    Write-Host "[OK] Letter::outgoingLetter()" -ForegroundColor Green
}

# -------------------------------------------------------------------------
# 2. Detail SPT class: preload relation in mount / publish / cancel.
# -------------------------------------------------------------------------
$path = "app\Livewire\Letters\Show.php"
$content = Read-Utf8 $path

if ($content -notmatch "'outgoingLetter'") {
    $content = $content.Replace(
        "            'attachments',",
        "            'attachments',`r`n            'outgoingLetter',"
    )

    Write-Utf8NoBom $path $content
    Write-Host "[OK] Detail SPT preload outgoingLetter" -ForegroundColor Green
}

# -------------------------------------------------------------------------
# 3. Detail SPT UI: preserve current design, only make action idempotent.
# -------------------------------------------------------------------------
$path = "resources\views\livewire\letters\show.blade.php"
$content = Read-Utf8 $path

$old = @'
                @can('outgoing-letters.create')
                    <a
                        href="{{ route('outgoing-letters.create', ['source_spt' => $letter->id]) }}"
                        wire:navigate
                        class="spt-action spt-action-primary"
                    >
                        <x-app.icon name="document" />
                        Terbitkan Surat
                    </a>
                @endcan
'@

$new = @'
                @can('outgoing-letters.create')
                    @if($letter->outgoingLetter)
                        <a
                            href="{{ route('outgoing-letters.show', $letter->outgoingLetter) }}"
                            wire:navigate
                            class="spt-action spt-action-view"
                        >
                            <x-app.icon name="document" />
                            Lihat Surat Keluar
                        </a>
                    @elseif($letter->status === \App\Enums\LetterStatus::Published)
                        <a
                            href="{{ route('outgoing-letters.create', ['source_spt' => $letter->id]) }}"
                            wire:navigate
                            class="spt-action spt-action-primary"
                        >
                            <x-app.icon name="document" />
                            Terbitkan Surat
                        </a>
                    @endif
                @endcan
'@

if ($content.Contains($old)) {
    $content = $content.Replace($old, $new)
    Write-Utf8NoBom $path $content
    Write-Host "[OK] Detail SPT action idempotent" -ForegroundColor Green
} elseif ($content -notmatch 'Lihat Surat Keluar') {
    throw "Blok tombol Terbitkan Surat pada Detail SPT tidak ditemukan."
}

# -------------------------------------------------------------------------
# 4. Outgoing Create: redirect to existing letter before source hydration.
# -------------------------------------------------------------------------
$path = "app\Livewire\OutgoingLetters\Create.php"
$content = Read-Utf8 $path

if ($content -notmatch 'existingOutgoing') {
    $anchor = @'
        if ($sourceId) {
            $source = Letter::query()
'@

    $replacement = @'
        if ($sourceId) {
            $existingOutgoing = OutgoingLetter::query()
                ->where('source_spt_id', $sourceId)
                ->first();

            if ($existingOutgoing) {
                $this->redirectRoute(
                    'outgoing-letters.show',
                    $existingOutgoing,
                    navigate: true
                );

                return;
            }

            $source = Letter::query()
'@

    if (-not $content.Contains($anchor)) {
        throw "Anchor source_spt pada Outgoing Create tidak ditemukan."
    }

    $content = $content.Replace($anchor, $replacement)
    Write-Utf8NoBom $path $content
    Write-Host "[OK] Duplicate SPT -> Surat Keluar protection" -ForegroundColor Green
}

# -------------------------------------------------------------------------
# 5. Outgoing Show class: UX feedback, source relation, final preview state.
# -------------------------------------------------------------------------
$path = "app\Livewire\OutgoingLetters\Show.php"
$content = Read-Utf8 $path

$content = $content.Replace(
@'
        $service->verify($this->letter, auth()->user());
        $this->refreshLetter();
'@,
@'
        $service->verify($this->letter, auth()->user());
        $this->refreshLetter();

        session()->flash(
            'success',
            'Surat berhasil diverifikasi.'
        );
'@
)

$content = $content.Replace(
@'
        $service->approve($this->letter, auth()->user());
        $this->refreshLetter();
'@,
@'
        $service->approve($this->letter, auth()->user());
        $this->refreshLetter();

        session()->flash(
            'success',
            'Surat berhasil disetujui.'
        );
'@
)

$content = $content.Replace(
@'
        $service->send($this->letter, auth()->user());
        $this->refreshLetter();
'@,
@'
        $service->send($this->letter, auth()->user());
        $this->refreshLetter();

        session()->flash(
            'success',
            'Surat berhasil ditandai sebagai dikirim.'
        );
'@
)

$content = $content.Replace(
@'
        $service->archive($this->letter, auth()->user());
        $this->refreshLetter();
'@,
@'
        $service->archive($this->letter, auth()->user());
        $this->refreshLetter();

        session()->flash(
            'success',
            'Surat berhasil diarsipkan.'
        );
'@
)

$content = $content.Replace(
@'
                $this->letter->status !== OutgoingLetterStatus::Published,
'@,
@'
                ! in_array(
                    $this->letter->status,
                    [
                        OutgoingLetterStatus::Published,
                        OutgoingLetterStatus::Sent,
                        OutgoingLetterStatus::Archived,
                    ],
                    true
                ),
'@
)

if ($content -notmatch "'sourceSpt.activityType'") {
    $content = $content.Replace(
        "                'issuedLetter',",
        "                'issuedLetter',`r`n                'sourceSpt.activityType',`r`n                'personnels',"
    )
}

Write-Utf8NoBom $path $content
Write-Host "[OK] Surat Keluar workflow feedback + source relation" -ForegroundColor Green

# -------------------------------------------------------------------------
# 6. Outgoing Show view: source SPT info + loading/confirm. No layout rewrite.
# -------------------------------------------------------------------------
$path = "resources\views\livewire\outgoing-letters\show.blade.php"
$content = Read-Utf8 $path

if ($content -notmatch 'SPT Sumber') {
    $anchor = @'
                <div class="md:col-span-2">
                    <dt class="text-xs text-slate-500">Perihal</dt>
                    <dd class="mt-1 font-semibold">{{ $letter->subject }}</dd>
                </div>
'@

    $addition = @'
                <div class="md:col-span-2">
                    <dt class="text-xs text-slate-500">Perihal</dt>
                    <dd class="mt-1 font-semibold">{{ $letter->subject }}</dd>
                </div>

                @if($letter->sourceSpt)
                    <div class="md:col-span-2">
                        <dt class="text-xs text-slate-500">SPT Sumber</dt>
                        <dd class="mt-1">
                            <a
                                href="{{ route('letters.show', $letter->sourceSpt) }}"
                                wire:navigate
                                class="font-medium text-blue-700 hover:underline"
                            >
                                {{ $letter->sourceSpt->number ?: 'SPT #'.$letter->sourceSpt->id }}
                                · {{ $letter->sourceSpt->subject }}
                            </a>
                        </dd>
                    </div>
                @endif
'@

    if (-not $content.Contains($anchor)) {
        throw "Anchor Perihal pada Detail Surat Keluar tidak ditemukan."
    }

    $content = $content.Replace($anchor, $addition)
}

$content = $content.Replace(
@'
                        <button wire:click="verify" class="spt-action spt-action-primary">Verifikasi</button>
'@,
@'
                        <button
                            type="button"
                            wire:click="verify"
                            wire:loading.attr="disabled"
                            wire:target="verify"
                            wire:confirm="Verifikasi surat ini?"
                            class="spt-action spt-action-primary"
                        >
                            <span wire:loading.remove wire:target="verify">Verifikasi</span>
                            <span wire:loading wire:target="verify">Memproses...</span>
                        </button>
'@
)

$content = $content.Replace(
@'
                        <button wire:click="approve" class="spt-action spt-action-primary">Setujui</button>
'@,
@'
                        <button
                            type="button"
                            wire:click="approve"
                            wire:loading.attr="disabled"
                            wire:target="approve"
                            wire:confirm="Setujui surat ini untuk proses penerbitan?"
                            class="spt-action spt-action-primary"
                        >
                            <span wire:loading.remove wire:target="approve">Setujui</span>
                            <span wire:loading wire:target="approve">Memproses...</span>
                        </button>
'@
)

$content = $content.Replace(
@'
                        <button
                            wire:click="publish"
                            wire:confirm="Terbitkan surat ini? Nomor resmi akan dialokasikan dan surat masuk Register Surat Terbit."
                            class="spt-action spt-action-primary"
                        >
                            Terbitkan Surat
                        </button>
'@,
@'
                        <button
                            type="button"
                            wire:click="publish"
                            wire:loading.attr="disabled"
                            wire:target="publish"
                            wire:confirm="Terbitkan surat ini? Nomor resmi akan dialokasikan dan surat masuk Register Surat Terbit."
                            class="spt-action spt-action-primary"
                        >
                            <span wire:loading.remove wire:target="publish">Terbitkan Surat</span>
                            <span wire:loading wire:target="publish">Menerbitkan...</span>
                        </button>
'@
)

$content = $content.Replace(
@'
                        <button wire:click="send" class="spt-action spt-action-primary">Tandai Dikirim</button>
'@,
@'
                        <button
                            type="button"
                            wire:click="send"
                            wire:loading.attr="disabled"
                            wire:target="send"
                            wire:confirm="Tandai surat ini sebagai sudah dikirim?"
                            class="spt-action spt-action-primary"
                        >
                            <span wire:loading.remove wire:target="send">Tandai Dikirim</span>
                            <span wire:loading wire:target="send">Memproses...</span>
                        </button>
'@
)

$content = $content.Replace(
@'
                        <button wire:click="archive" class="spt-action spt-action-primary">Arsipkan</button>
'@,
@'
                        <button
                            type="button"
                            wire:click="archive"
                            wire:loading.attr="disabled"
                            wire:target="archive"
                            wire:confirm="Arsipkan surat ini?"
                            class="spt-action spt-action-primary"
                        >
                            <span wire:loading.remove wire:target="archive">Arsipkan</span>
                            <span wire:loading wire:target="archive">Mengarsipkan...</span>
                        </button>
'@
)

$content = $content.Replace(
@'
        'previewMode' => $letter->status !== \App\Enums\OutgoingLetterStatus::Published,
'@,
@'
        'previewMode' => ! in_array(
            $letter->status,
            [
                \App\Enums\OutgoingLetterStatus::Published,
                \App\Enums\OutgoingLetterStatus::Sent,
                \App\Enums\OutgoingLetterStatus::Archived,
            ],
            true
        ),
'@
)

Write-Utf8NoBom $path $content
Write-Host "[OK] Detail Surat Keluar UX stabilization" -ForegroundColor Green

# -------------------------------------------------------------------------
# 7. Incoming workflow: success feedback and loading state.
# -------------------------------------------------------------------------
$path = "app\Livewire\IncomingLetters\Show.php"
$content = Read-Utf8 $path

$content = $content.Replace(
@'
        $service->dispose($this->letter, auth()->user());
        $this->letter->refresh();
'@,
@'
        $service->dispose($this->letter, auth()->user());
        $this->letter->refresh();

        session()->flash(
            'success',
            'Surat berhasil ditandai sebagai didisposisikan.'
        );
'@
)

$content = $content.Replace(
@'
        $service->process($this->letter, auth()->user());
        $this->letter->refresh();
'@,
@'
        $service->process($this->letter, auth()->user());
        $this->letter->refresh();

        session()->flash(
            'success',
            'Tindak lanjut surat berhasil dimulai.'
        );
'@
)

$content = $content.Replace(
@'
        $service->complete($this->letter, auth()->user());
        $this->letter->refresh();
'@,
@'
        $service->complete($this->letter, auth()->user());
        $this->letter->refresh();

        session()->flash(
            'success',
            'Tindak lanjut surat berhasil diselesaikan.'
        );
'@
)

$content = $content.Replace(
@'
        $service->archive($this->letter, auth()->user());
        $this->letter->refresh();
'@,
@'
        $service->archive($this->letter, auth()->user());
        $this->letter->refresh();

        session()->flash(
            'success',
            'Surat berhasil diarsipkan.'
        );
'@
)

Write-Utf8NoBom $path $content
Write-Host "[OK] Surat Masuk workflow feedback" -ForegroundColor Green

$path = "resources\views\livewire\incoming-letters\show.blade.php"
$content = Read-Utf8 $path

if ($content -notmatch "session\('success'\)") {
    $anchor = @'
    </x-app.page-heading>

'@

    $addition = @'
    </x-app.page-heading>

    @if(session('success'))
        <div
            role="status"
            class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"
        >
            {{ session('success') }}
        </div>
    @endif

'@

    if (-not $content.Contains($anchor)) {
        throw "Anchor page-heading Surat Masuk tidak ditemukan."
    }

    $content = $content.Replace($anchor, $addition)
}

$content = $content.Replace(
@'
@if($letter->status === \App\Enums\IncomingLetterStatus::Recorded) @can('incoming-letters.process')<button wire:click="dispose" class="spt-action spt-action-primary">Tandai Didisposisikan</button>@endcan
'@,
@'
@if($letter->status === \App\Enums\IncomingLetterStatus::Recorded) @can('incoming-letters.process')<button type="button" wire:click="dispose" wire:loading.attr="disabled" wire:target="dispose" wire:confirm="Tandai surat ini sebagai sudah didisposisikan?" class="spt-action spt-action-primary"><span wire:loading.remove wire:target="dispose">Tandai Didisposisikan</span><span wire:loading wire:target="dispose">Memproses...</span></button>@endcan
'@
)

$content = $content.Replace(
@'
@elseif($letter->status === \App\Enums\IncomingLetterStatus::Disposed) @can('incoming-letters.process')<button wire:click="process" class="spt-action spt-action-primary">Mulai Proses</button>@endcan
'@,
@'
@elseif($letter->status === \App\Enums\IncomingLetterStatus::Disposed) @can('incoming-letters.process')<button type="button" wire:click="process" wire:loading.attr="disabled" wire:target="process" class="spt-action spt-action-primary"><span wire:loading.remove wire:target="process">Mulai Proses</span><span wire:loading wire:target="process">Memproses...</span></button>@endcan
'@
)

$content = $content.Replace(
@'
@elseif($letter->status === \App\Enums\IncomingLetterStatus::Processing) @can('incoming-letters.process')<button wire:click="complete" class="spt-action spt-action-primary">Tandai Selesai</button>@endcan
'@,
@'
@elseif($letter->status === \App\Enums\IncomingLetterStatus::Processing) @can('incoming-letters.process')<button type="button" wire:click="complete" wire:loading.attr="disabled" wire:target="complete" wire:confirm="Tandai tindak lanjut surat ini sebagai selesai?" class="spt-action spt-action-primary"><span wire:loading.remove wire:target="complete">Tandai Selesai</span><span wire:loading wire:target="complete">Memproses...</span></button>@endcan
'@
)

$content = $content.Replace(
@'
@elseif($letter->status === \App\Enums\IncomingLetterStatus::Completed) @can('incoming-letters.archive')<button wire:click="archive" class="spt-action spt-action-primary">Arsipkan</button>@endcan
'@,
@'
@elseif($letter->status === \App\Enums\IncomingLetterStatus::Completed) @can('incoming-letters.archive')<button type="button" wire:click="archive" wire:loading.attr="disabled" wire:target="archive" wire:confirm="Arsipkan surat ini?" class="spt-action spt-action-primary"><span wire:loading.remove wire:target="archive">Arsipkan</span><span wire:loading wire:target="archive">Mengarsipkan...</span></button>@endcan
'@
)

Write-Utf8NoBom $path $content
Write-Host "[OK] Detail Surat Masuk UX stabilization" -ForegroundColor Green

# -------------------------------------------------------------------------
# 8. Copy test/docs only. No CSS/layout/dashboard changes.
# -------------------------------------------------------------------------
foreach ($relative in @(
    "tests\Feature\PhaseNineteenV2StabilizationTest.php",
    "docs\PHASE19-V2.md"
)) {
    $source = Join-Path $PSScriptRoot $relative
    $target = Join-Path (Get-Location) $relative
    $parent = Split-Path $target -Parent

    if (-not (Test-Path $parent)) {
        New-Item -ItemType Directory -Force -Path $parent | Out-Null
    }

    $fileContent = [System.IO.File]::ReadAllText($source)

    if (Test-Path $target) {
        [System.IO.File]::WriteAllText((Resolve-Path $target), $fileContent, $utf8NoBom)
    } else {
        [System.IO.File]::WriteAllText($target, $fileContent, $utf8NoBom)
    }

    Write-Host "[OK] $relative" -ForegroundColor Green
}

Write-Host ""
Write-Host "[OK] Phase 19 v2 terpasang." -ForegroundColor Green
Write-Host "UI commit terbaru dipertahankan; app.css, dashboard, kop, template, numbering, dan renderer tidak disentuh." -ForegroundColor Cyan
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan view:clear"
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact --filter=PhaseNineteenV2StabilizationTest"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
