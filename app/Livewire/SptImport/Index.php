<?php

namespace App\Livewire\SptImport;

use App\Models\ImportBatch;
use App\Services\GoogleSheetReader;
use App\Services\SptImportService;
use App\Services\TabularFileReader;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;
use App\Models\GoogleConnection;

class Index extends Component
{
    use WithFileUploads;

    public $file;

    /**
     * file | google_api | google_public
     */
    public string $sourceType = 'file';

    public string $sheetUrl = '';

    public string $sheetName = '';

    public string $sheetRange = 'A1:K200';

    public array $availableSheets = [];

    public bool $loadingSheets = false;

    public ?string $sourceLabel = null;

    public array $headers = [];

    public array $rows = [];

    public array $mapping = [
        'number' => '',
        'letter_date' => '',
        'start_date' => '',
        'end_date' => '',
        'subject' => '',
        'activity' => '',
        'location' => '',
        'personnel_names' => '',
        'personnel_nips' => '',
        'description' => '',
        'record_type' => '',
    ];

    public int $step = 1;

    public ?int $lastBatchId = null;

    public function mount(): void
    {
        Gate::authorize('letters.import');
    }

    public function updatedSourceType(): void
    {
        $this->resetValidation();

        $this->headers = [];
        $this->rows = [];
        $this->availableSheets = [];
        $this->sheetName = '';
        $this->sourceLabel = null;
    }

    /**
     * Import dari CSV/XLSX.
     */
    public function readFile(
        TabularFileReader $reader
    ): void {
        Gate::authorize('letters.import');

        $this->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx',
                'max:10240',
            ],
        ]);

        $extension = strtolower(
            $this->file->getClientOriginalExtension()
        );

        $data = $reader->read(
            $this->file->getRealPath(),
            $extension === 'txt' ? 'csv' : $extension
        );

        if ($data['headers'] === []) {
            $this->addError(
                'file',
                'File tidak memiliki header/data yang dapat dibaca.'
            );

            return;
        }

        $this->applySourceData(
            $data,
            $this->file->getClientOriginalName()
        );
    }

    /**
     * API mode:
     * mengambil daftar tab sehingga user tidak perlu mengetik nama tab.
     */
    public function loadGoogleSheets(
        GoogleSheetReader $reader
    ): void {
        Gate::authorize('letters.import');

        $this->resetErrorBag('sheetUrl');

        $this->validate([
            'sheetUrl' => [
                'required',
                'string',
                'max:2000',
            ],
        ], [
            'sheetUrl.required' => 'URL Google Sheets wajib diisi.',
        ]);

        try {
            $this->loadingSheets = true;

            $this->availableSheets = $reader->listSheetsApi(
                auth()->id(),  
                $this->sheetUrl
            );

            if ($this->availableSheets === []) {
                $this->addError(
                    'sheetUrl',
                    'Spreadsheet tidak memiliki sheet yang dapat dibaca.'
                );

                return;
            }

            if ($this->sheetName === '') {
                $this->sheetName = (string) (
                    $this->availableSheets[0]['title'] ?? ''
                );
            }
        } catch (Throwable $e) {
            report($e);

            $this->addError(
                'sheetUrl',
                $e->getMessage()
            );
        } finally {
            $this->loadingSheets = false;
        }
    }

    /**
     * Membaca range dari Google Sheets.
     */
    public function readGoogleSheet(
        GoogleSheetReader $reader
    ): void {
        Gate::authorize('letters.import');

        if ($this->sourceType === 'google_api') {
            $this->readGoogleApi($reader);

            return;
        }

        if ($this->sourceType === 'google_public') {
            $this->readGooglePublic($reader);

            return;
        }

        $this->addError(
            'sourceType',
            'Sumber Google Sheets tidak valid.'
        );
    }

    private function readGoogleApi(
        GoogleSheetReader $reader
    ): void {
        $this->validate([
            'sheetUrl' => [
                'required',
                'string',
                'max:2000',
            ],

            'sheetName' => [
                'required',
                'string',
                'max:255',
            ],

            'sheetRange' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z]+\d*:[A-Za-z]+\d*$/',
            ],
        ], [
            'sheetUrl.required' => 'URL Google Sheets wajib diisi.',

            'sheetName.required' => 'Pilih sheet terlebih dahulu.',

            'sheetRange.required' => 'Range wajib diisi.',

            'sheetRange.regex' => 'Gunakan format range seperti A1:K100.',
        ]);

        try {
            $data = $reader->readApi(
                auth()->id(),
                $this->sheetUrl,
                $this->sheetName,
                strtoupper($this->sheetRange)
            );
        } catch (Throwable $e) {
            report($e);

            $this->addError(
                'sheetUrl',
                $e->getMessage()
            );

            return;
        }

        if ($data['headers'] === []) {
            $this->addError(
                'sheetRange',
                'Range tidak menghasilkan data.'
            );

            return;
        }

        $this->applySourceData(
            $data,
            sprintf(
                'Google Sheets API - %s - %s',
                $this->sheetName,
                strtoupper($this->sheetRange)
            )
        );
    }

    private function readGooglePublic(
        GoogleSheetReader $reader
    ): void {
        $this->validate([
            'sheetUrl' => [
                'required',
                'string',
                'max:2000',
            ],

            'sheetRange' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z]+\d*:[A-Za-z]+\d*$/',
            ],
        ], [
            'sheetUrl.required' => 'URL Google Sheets wajib diisi.',

            'sheetRange.required' => 'Range wajib diisi.',

            'sheetRange.regex' => 'Gunakan format range seperti A1:K100.',
        ]);

        try {
            $data = $reader->readPublic(
                $this->sheetUrl,
                strtoupper($this->sheetRange)
            );
        } catch (Throwable $e) {
            report($e);

            $this->addError(
                'sheetUrl',
                $e->getMessage()
            );

            return;
        }

        if ($data['headers'] === []) {
            $this->addError(
                'sheetRange',
                'Range tidak menghasilkan data.'
            );

            return;
        }

        $this->applySourceData(
            $data,
            sprintf(
                'Google Sheets Public - %s',
                strtoupper($this->sheetRange)
            )
        );
    }

    private function applySourceData(
        array $data,
        string $label
    ): void {
        $this->headers = $data['headers'];
        $this->rows = $data['rows'];
        $this->sourceLabel = $label;

        $this->autoMap();

        $this->step = 2;
    }

    public function goPreview(): void
    {
        $this->validate([
            'mapping.number' => [
                'required',
                'string',
            ],

            'mapping.letter_date' => [
                'nullable',
                'string',
            ],

            'mapping.activity' => [
                'nullable',
                'string',
            ],

            'mapping.personnel_names' => [
                'nullable',
                'string',
            ],
        ], [
            'mapping.number.required' => 'Kolom Nomor SPT wajib dipetakan.',
        ]);

        $this->step = 3;
    }

    public function import(
        SptImportService $service
    ): void {
        Gate::authorize('letters.import');

        $sourceName = match ($this->sourceType) {
            'google_api',
            'google_public' => $this->sourceLabel ?? 'Google Sheets',

            default => $this->file?->getClientOriginalName()
                    ?? 'Import SPT',
        };

        $batch = $service->import(
            $this->rows,
            $this->mapping,
            $sourceName,
            auth()->id()
        );

        $this->lastBatchId = $batch->id;
        $this->step = 4;
    }

    public function restart(): void
    {
        $this->reset([
            'file',
            'headers',
            'rows',
            'lastBatchId',
            'sheetUrl',
            'sheetName',
            'availableSheets',
            'sourceLabel',
        ]);

        $this->sourceType = 'file';
        $this->sheetRange = 'A1:K200';

        $this->mapping = array_fill_keys(
            array_keys($this->mapping),
            ''
        );

        $this->step = 1;

        $this->resetValidation();
    }

    private function autoMap(): void
    {
        $rules = [
            'number' => [
                'nomor spt',
                'no spt',
                'nomor',
                'no surat',
                'nomor surat',
            ],

            'letter_date' => [
                'tanggal spt',
                'tanggal surat',
                'tgl spt',
                'tanggal',
            ],

            'start_date' => [
                'tanggal mulai',
                'tgl mulai',
                'mulai',
            ],

            'end_date' => [
                'tanggal selesai',
                'tgl selesai',
                'selesai',
            ],

            'subject' => [
                'perihal',
                'subjek',
                'subject',
            ],

            'activity' => [
                'kegiatan',
                'jenis kegiatan',
                'aktivitas',
            ],

            'location' => [
                'lokasi',
                'tempat',
                'wilayah',
            ],

            'personnel_names' => [
                'personil',
                'nama personil',
                'pegawai',
                'nama pegawai',
            ],

            'personnel_nips' => [
                'nip',
                'nip personil',
                'nip pegawai',
            ],

            'description' => [
                'keterangan',
                'deskripsi',
                'catatan',
            ],

            'record_type' => [
                'jenis record',
                'jenis spt',
                'tipe spt',
                'kategori spt',
                'record type',
            ],
        ];

        foreach ($rules as $field => $candidates) {
            foreach ($this->headers as $header) {
                if (
                    in_array(
                        mb_strtolower(trim($header)),
                        $candidates,
                        true
                    )
                ) {
                    $this->mapping[$field] = $header;

                    break;
                }
            }
        }
    }

    public function render()
    {
        return view(
            'livewire.spt-import.index',
            [
                'previewRows' => array_slice(
                    $this->rows,
                    0,
                    10
                ),

                'lastBatch' => $this->lastBatchId
                        ? ImportBatch::find(
                            $this->lastBatchId
                        )
                        : null,

                'recentBatches' => ImportBatch::query()
                    ->with('uploader')
                    ->latest()
                    ->limit(8)
                    ->get(),

                'googleConnected' => GoogleConnection::query()
                    ->where('user_id', auth()->id())
                    ->exists(),
            ]
        );
    }
}
