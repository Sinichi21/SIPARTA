<?php

namespace App\Livewire\SptImport;

use App\Models\ImportBatch;
use App\Services\SptImportService;
use App\Services\TabularFileReader;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public $file;
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

    public function readFile(TabularFileReader $reader): void
    {
        Gate::authorize('letters.import');

        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $extension = strtolower($this->file->getClientOriginalExtension());
        $data = $reader->read($this->file->getRealPath(), $extension === 'txt' ? 'csv' : $extension);

        if ($data['headers'] === []) {
            $this->addError('file', 'File tidak memiliki header/data yang dapat dibaca.');
            return;
        }

        $this->headers = $data['headers'];
        $this->rows = $data['rows'];
        $this->autoMap();
        $this->step = 2;
    }

    public function goPreview(): void
    {
        $this->validate([
            'mapping.number' => ['required', 'string'],
            'mapping.letter_date' => ['nullable', 'string'],
            'mapping.activity' => ['nullable', 'string'],
            'mapping.personnel_names' => ['nullable', 'string'],
        ], [
            'mapping.number.required' => 'Kolom Nomor SPT wajib dipetakan.',
        ]);

        $this->step = 3;
    }

    public function import(SptImportService $service): void
    {
        Gate::authorize('letters.import');

        $batch = $service->import(
            $this->rows,
            $this->mapping,
            $this->file->getClientOriginalName(),
            auth()->id()
        );

        $this->lastBatchId = $batch->id;
        $this->step = 4;
    }

    public function restart(): void
    {
        $this->reset(['file', 'headers', 'rows', 'lastBatchId']);
        $this->mapping = array_fill_keys(array_keys($this->mapping), '');
        $this->step = 1;
        $this->resetValidation();
    }

    private function autoMap(): void
    {
        $rules = [
            'number' => ['nomor spt', 'no spt', 'nomor', 'no surat', 'nomor surat'],
            'letter_date' => ['tanggal spt', 'tanggal surat', 'tgl spt', 'tanggal'],
            'start_date' => ['tanggal mulai', 'tgl mulai', 'mulai'],
            'end_date' => ['tanggal selesai', 'tgl selesai', 'selesai'],
            'subject' => ['perihal', 'subjek', 'subject'],
            'activity' => ['kegiatan', 'jenis kegiatan', 'aktivitas'],
            'location' => ['lokasi', 'tempat', 'wilayah'],
            'personnel_names' => ['personil', 'nama personil', 'pegawai', 'nama pegawai'],
            'personnel_nips' => ['nip', 'nip personil', 'nip pegawai'],
            'description' => ['keterangan', 'deskripsi', 'catatan'],
            'record_type' => ['jenis record', 'jenis spt', 'tipe spt', 'kategori spt', 'record type'],
        ];

        foreach ($rules as $field => $candidates) {
            foreach ($this->headers as $header) {
                if (in_array(mb_strtolower(trim($header)), $candidates, true)) {
                    $this->mapping[$field] = $header;
                    break;
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.spt-import.index', [
            'previewRows' => array_slice($this->rows, 0, 10),
            'lastBatch' => $this->lastBatchId ? ImportBatch::find($this->lastBatchId) : null,
            'recentBatches' => ImportBatch::query()->with('uploader')->latest()->limit(8)->get(),
        ]);
    }
}
