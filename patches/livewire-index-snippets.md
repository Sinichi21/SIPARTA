# Patch to existing `app/Livewire/SptImport/Index.php`

**Keep** all existing SPT mappings, `autoMap()`, `goPreview()`, `render()`, and `SptImportService`. Do not replace the full Livewire class: the repository may have local changes.

Add imports:

```php
use App\Models\GoogleConnection;
use App\Services\GoogleSheetReader;
use Throwable;
```

Add component properties:

```php
public string $sourceType = 'file'; // file | google_api | google_public
public string $sheetUrl = '';
public string $sheetName = '';
public string $sheetRange = 'A1:K200';
public array $availableSheets = [];
public string $sourceLabel = '';
```

Add methods:

```php
public function updatedSourceType(): void
{
    $this->resetValidation();
    $this->headers = [];
    $this->rows = [];
    $this->availableSheets = [];
    $this->sheetName = '';
}

public function loadGoogleSheets(GoogleSheetReader $reader): void
{
    Gate::authorize('letters.import');
    $this->validate(['sheetUrl' => ['required', 'string', 'max:2000']]);
    try {
        $this->availableSheets = $reader->listSheetsApi(auth()->id(), $this->sheetUrl);
        $this->sheetName = $this->availableSheets[0]['title'] ?? '';
    } catch (Throwable $e) {
        report($e);
        $this->addError('sheetUrl', 'Tidak dapat membaca daftar sheet. Periksa akun dan URL.');
    }
}

public function readGoogleSheet(GoogleSheetReader $reader): void
{
    Gate::authorize('letters.import');
    $this->validate([
        'sourceType' => ['required', 'in:google_api,google_public'],
        'sheetUrl' => ['required', 'string', 'max:2000'],
        'sheetRange' => ['required', 'regex:/^[A-Za-z]{1,3}[1-9]\\d*:[A-Za-z]{1,3}[1-9]\\d*$/'],
        'sheetName' => [$this->sourceType === 'google_api' ? 'required' : 'nullable', 'string'],
    ]);
    try {
        $data = $this->sourceType === 'google_api'
            ? $reader->readApi(auth()->id(), $this->sheetUrl, $this->sheetName, $this->sheetRange)
            : $reader->readPublic($this->sheetUrl, $this->sheetRange);
        if ($data['headers'] === []) {
            $this->addError('sheetRange', 'Range tidak mengandung header.');
            return;
        }
        $this->headers = $data['headers'];
        $this->rows = $data['rows'];
        $this->sourceLabel = 'Google Sheets '.($this->sourceType === 'google_api' ? 'OAuth' : 'Public')
            .' / '.$this->sheetRange;
        $this->autoMap();
        $this->step = 2;
    } catch (Throwable $e) {
        report($e);
        $this->addError('sheetUrl', 'Gagal membaca spreadsheet. Periksa izin, URL, tab, dan range.');
    }
}
```

In `import()`, replace the filename argument in `$service->import(...)`:

```php
$this->sourceType === 'file'
    ? $this->file->getClientOriginalName()
    : $this->sourceLabel,
```

In `restart()` add reset of source fields and set sourceType to file. Keep original mapping reset.

In `render()` you can expose OAuth connection to Blade with:

```php
'googleConnected' => GoogleConnection::query()->where('user_id', auth()->id())->exists(),
```

See `blade-step1.blade.php` for the replacement `@if($step === 1)` branch. Keep the existing remainder `@elseif($step === 2)` onward.
