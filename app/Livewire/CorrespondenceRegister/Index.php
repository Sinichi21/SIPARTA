<?php

namespace App\Livewire\CorrespondenceRegister;

use App\Models\IncomingLetter;
use App\Models\IssuedLetter;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    use WithPagination;

    public string $documentType = 'issued';
    public string $search = '';
    public string $year = '';
    public string $month = '';
    public string $status = '';
    public string $letterTypeId = '';

    public function mount(): void
    {
        Gate::authorize('reports.view');

        $this->year = (string) now()->year;
    }

    public function updatedDocumentType(): void
    {
        $this->status = '';
        $this->letterTypeId = '';
        $this->resetPage();
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->year = (string) now()->year;
        $this->month = '';
        $this->status = '';
        $this->letterTypeId = '';
        $this->resetPage();
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('reports.export');

        $rows = $this->baseQuery()
            ->limit(5000)
            ->get();

        $filename = sprintf(
            'register-%s-%s.csv',
            $this->documentType,
            now()->format('Ymd-His')
        );

        return response()->streamDownload(
            function () use ($rows): void {
                $handle = fopen('php://output', 'w');

                // UTF-8 BOM helps Excel on Windows.
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, $this->csvHeaders());

                foreach ($rows as $row) {
                    fputcsv($handle, $this->csvRow($row));
                }

                fclose($handle);
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function render()
    {
        $query = $this->baseQuery();

        return view('livewire.correspondence-register.index', [
            'rows' => $query->paginate(20),
            'letterTypes' => LetterType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'summary' => $this->summary(),
        ]);
    }

    private function baseQuery(): Builder
    {
        return match ($this->documentType) {
            'incoming' => $this->incomingQuery(),
            'outgoing' => $this->outgoingQuery(),
            default => $this->issuedQuery(),
        };
    }

    private function incomingQuery(): Builder
    {
        return IncomingLetter::query()
            ->when(
                filled($this->search),
                function (Builder $query): void {
                    $search = trim($this->search);

                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->whereLike('agenda_number', "%{$search}%")
                            ->orWhereLike('number', "%{$search}%")
                            ->orWhereLike('sender', "%{$search}%")
                            ->orWhereLike('subject', "%{$search}%");
                    });
                }
            )
            ->when(
                filled($this->status),
                fn (Builder $query) => $query->where('status', $this->status)
            )
            ->when(
                filled($this->year),
                fn (Builder $query) => $query->whereYear('received_date', $this->year)
            )
            ->when(
                filled($this->month),
                fn (Builder $query) => $query->whereMonth('received_date', $this->month)
            )
            ->latest('received_date')
            ->latest('id');
    }

    private function outgoingQuery(): Builder
    {
        return OutgoingLetter::query()
            ->with('letterType')
            ->when(
                filled($this->search),
                function (Builder $query): void {
                    $search = trim($this->search);

                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->whereLike('number', "%{$search}%")
                            ->orWhereLike('recipient', "%{$search}%")
                            ->orWhereLike('subject', "%{$search}%");
                    });
                }
            )
            ->when(
                filled($this->status),
                fn (Builder $query) => $query->where('status', $this->status)
            )
            ->when(
                filled($this->letterTypeId),
                fn (Builder $query) => $query->where('letter_type_id', $this->letterTypeId)
            )
            ->when(
                filled($this->year),
                fn (Builder $query) => $query->whereYear('created_at', $this->year)
            )
            ->when(
                filled($this->month),
                fn (Builder $query) => $query->whereMonth('created_at', $this->month)
            )
            ->latest('created_at')
            ->latest('id');
    }

    private function issuedQuery(): Builder
    {
        return IssuedLetter::query()
            ->with(['letterType', 'issuer'])
            ->when(
                filled($this->search),
                function (Builder $query): void {
                    $search = trim($this->search);

                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->whereLike('number', "%{$search}%")
                            ->orWhereLike('recipient', "%{$search}%")
                            ->orWhereLike('subject', "%{$search}%");
                    });
                }
            )
            ->when(
                filled($this->status),
                fn (Builder $query) => $query->where('status', $this->status)
            )
            ->when(
                filled($this->letterTypeId),
                fn (Builder $query) => $query->where('letter_type_id', $this->letterTypeId)
            )
            ->when(
                filled($this->year),
                fn (Builder $query) => $query->whereYear('letter_date', $this->year)
            )
            ->when(
                filled($this->month),
                fn (Builder $query) => $query->whereMonth('letter_date', $this->month)
            )
            ->latest('letter_date')
            ->latest('id');
    }

    private function summary(): array
    {
        $year = filled($this->year)
            ? (int) $this->year
            : now()->year;

        return [
            'incoming' => IncomingLetter::query()
                ->whereYear('received_date', $year)
                ->count(),
            'outgoing' => OutgoingLetter::query()
                ->whereYear('created_at', $year)
                ->count(),
            'issued' => IssuedLetter::query()
                ->whereYear('letter_date', $year)
                ->count(),
            'revoked' => IssuedLetter::query()
                ->whereYear('letter_date', $year)
                ->where('status', 'revoked')
                ->count(),
        ];
    }

    private function csvHeaders(): array
    {
        return match ($this->documentType) {
            'incoming' => [
                'Agenda',
                'Nomor',
                'Tanggal Surat',
                'Tanggal Diterima',
                'Asal',
                'Perihal',
                'Status',
            ],
            'outgoing' => [
                'Nomor',
                'Jenis',
                'Tanggal',
                'Tujuan',
                'Perihal',
                'Status',
            ],
            default => [
                'Nomor',
                'Jenis',
                'Tanggal',
                'Tujuan',
                'Perihal',
                'Status',
                'Diterbitkan Oleh',
                'Waktu Terbit',
            ],
        };
    }

    private function csvRow($row): array
    {
        return match ($this->documentType) {
            'incoming' => [
                $row->agenda_number,
                $row->number,
                $row->letter_date?->format('Y-m-d'),
                $row->received_date?->format('Y-m-d'),
                $row->sender,
                $row->subject,
                $row->status?->label() ?? (string) $row->status,
            ],
            'outgoing' => [
                $row->number ?: 'Draft #'.$row->id,
                $row->letterType?->name,
                $row->letter_date?->format('Y-m-d'),
                $row->recipient,
                $row->subject,
                $row->status?->label() ?? (string) $row->status,
            ],
            default => [
                $row->number,
                $row->letterType?->name,
                $row->letter_date?->format('Y-m-d'),
                $row->recipient,
                $row->subject,
                ucfirst((string) $row->status),
                $row->issuer?->name,
                $row->issued_at?->format('Y-m-d H:i:s'),
            ],
        };
    }
}
