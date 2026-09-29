$ErrorActionPreference = "Stop"

$path = "app\Livewire\PersonnelRecap\Index.php"

if (-not (Test-Path $path)) {
    throw "File tidak ditemukan: $path"
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$content = [System.IO.File]::ReadAllText((Resolve-Path $path))

if ($content.Contains("'showAllHistory' => `$this->showAllHistory")) {
    Write-Host "[SKIP] showAllHistory sudah dikirim ke view." -ForegroundColor Yellow
    exit 0
}

$old = @'
                'selectedHistory' =>
                    $selectedHistory,

                'selectedFilteredCount' =>
'@

$new = @'
                'selectedHistory' =>
                    $selectedHistory,

                'showAllHistory' =>
                    $this->showAllHistory,

                'selectedFilteredCount' =>
'@

if (-not $content.Contains($old)) {
    throw "Blok target tidak ditemukan pada $path"
}

$content = $content.Replace($old, $new)

[System.IO.File]::WriteAllText(
    (Resolve-Path $path),
    $content,
    $utf8NoBom
)

Write-Host "[OK] showAllHistory dikirim eksplisit ke view." -ForegroundColor Green
Write-Host ""
Write-Host "Jalankan:" -ForegroundColor Cyan
Write-Host "php artisan view:clear"
Write-Host "php artisan test --compact --filter=RecapDetailTest"
Write-Host "php artisan test --compact"
