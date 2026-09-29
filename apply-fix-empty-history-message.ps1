$ErrorActionPreference = "Stop"

$path = "resources\views\livewire\personnel-recap\detail.blade.php"

if (-not (Test-Path $path)) {
    throw "File tidak ditemukan: $path"
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$content = [System.IO.File]::ReadAllText((Resolve-Path $path))

$old = @'
            <p class="detail-empty">
                Belum ada riwayat penugasan SPT
                pada filter yang sedang dipilih.
            </p>
'@

$new = @'
            <p class="detail-empty">
                Belum ada riwayat penugasan SPT.
                Pada filter yang sedang dipilih.
            </p>
'@

if ($content.Contains($new)) {
    Write-Host "[SKIP] Pesan empty history sudah diperbaiki." -ForegroundColor Yellow
    exit 0
}

if (-not $content.Contains($old)) {
    throw "Blok pesan empty history tidak ditemukan."
}

$content = $content.Replace($old, $new)

[System.IO.File]::WriteAllText(
    (Resolve-Path $path),
    $content,
    $utf8NoBom
)

Write-Host "[OK] Pesan empty history diperbaiki." -ForegroundColor Green
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan view:clear"
Write-Host "php artisan test --compact --filter=RecapDetailTest"
Write-Host "php artisan test --compact"
