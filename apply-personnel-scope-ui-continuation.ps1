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

function Replace-RegexIfNeeded {
    param(
        [string]$Path,
        [string]$AlreadyContains,
        [string]$Pattern,
        [string]$Replacement,
        [string]$Label
    )

    if (-not (Test-Path $Path)) {
        throw "File tidak ditemukan: $Path"
    }

    $content = Read-Utf8 $Path

    if ($content.Contains($AlreadyContains)) {
        Write-Host "[SKIP] $Label sudah terpasang" -ForegroundColor Yellow
        return
    }

    $updated = [regex]::Replace(
        $content,
        $Pattern,
        $Replacement,
        [System.Text.RegularExpressions.RegexOptions]::Singleline
    )

    if ($updated -eq $content) {
        throw "Tidak menemukan pola untuk: $Label`nFile: $Path"
    }

    Write-Utf8NoBom $Path $updated
    Write-Host "[OK] $Label" -ForegroundColor Green
}

# ============================================================
# 1. Data SPT
# ============================================================
Replace-RegexIfNeeded `
    -Path "resources\views\livewire\letters\index.blade.php" `
    -AlreadyContains "assignsAllPersonnel()" `
    -Pattern '<td class="px-4 py-3 text-sm">\s*\{\{\s*\$letter->personnels->count\(\)\s*\}\}\s*personil\s*</td>' `
    -Replacement @'
<td class="px-4 py-3 text-sm">
                                @if($letter->assignsAllPersonnel())
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                        Seluruh Pegawai
                                    </span>
                                @else
                                    {{ $letter->personnels->count() }} personil
                                @endif
                            </td>
'@ `
    -Label "Data SPT menampilkan Seluruh Pegawai"

# ============================================================
# 2. Rekap SPT
# ============================================================
Replace-RegexIfNeeded `
    -Path "resources\views\livewire\spt-recap\index.blade.php" `
    -AlreadyContains "assignsAllPersonnel() ? 'Seluruh Pegawai'" `
    -Pattern '\{\{\s*\$letter->personnels_count\s*\}\}\s*orang' `
    -Replacement "{{ `$letter->assignsAllPersonnel() ? 'Seluruh Pegawai' : `$letter->personnels_count.' orang' }}" `
    -Label "Rekap SPT menampilkan Seluruh Pegawai"

# ============================================================
# 3. Detail SPT - summary count
# ============================================================
Replace-RegexIfNeeded `
    -Path "resources\views\livewire\letters\show.blade.php" `
    -AlreadyContains "assignsAllPersonnel() ? 'Seluruh Pegawai'" `
    -Pattern '\{\{\s*\$letter->personnels->count\(\)\s*\}\}\s*orang' `
    -Replacement "{{ `$letter->assignsAllPersonnel() ? 'Seluruh Pegawai' : `$letter->personnels->count().' orang' }}" `
    -Label "Detail SPT summary menampilkan Seluruh Pegawai"

# ============================================================
# 4. Detail SPT - empty state
# ============================================================
$path = "resources\views\livewire\letters\show.blade.php"
$content = Read-Utf8 $path

if ($content.Contains("SPT ini berlaku untuk seluruh pegawai. Daftar personil individual tidak disimpan.")) {
    Write-Host "[SKIP] Detail SPT empty state sudah terpasang" -ForegroundColor Yellow
} else {
    $pattern = '<tr><td colspan="4" class="detail-empty">Belum ada personil yang ditugaskan\.</td></tr>'
    $replacement = @'
<tr>
                        <td colspan="4" class="detail-empty">
                            @if($letter->assignsAllPersonnel())
                                SPT ini berlaku untuk seluruh pegawai. Daftar personil individual tidak disimpan.
                            @else
                                Belum ada personil yang ditugaskan.
                            @endif
                        </td>
                    </tr>
'@
    $updated = [regex]::Replace($content, $pattern, $replacement)

    if ($updated -eq $content) {
        throw "Tidak menemukan empty state pada Detail SPT."
    }

    Write-Utf8NoBom $path $updated
    Write-Host "[OK] Detail SPT empty state Seluruh Pegawai" -ForegroundColor Green
}

Write-Host ""
Write-Host "Continuation selesai." -ForegroundColor Cyan
Write-Host "Lanjutkan:" -ForegroundColor Cyan
Write-Host "php artisan optimize:clear"
Write-Host "php artisan test --compact"
Write-Host "npm run build"
