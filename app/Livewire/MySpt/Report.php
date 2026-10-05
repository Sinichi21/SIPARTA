<?php

namespace App\Livewire\MySpt;

use App\Models\Letter;
use App\Models\SptReport;
use App\Support\PersonalLetterAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Report extends Component
{
    public Letter $letter;
    public ?SptReport $report = null;

    public string $activity_summary = '';
    public string $results = '';
    public string $obstacles = '';
    public string $follow_up = '';

    public function mount(
        Letter $letter,
        PersonalLetterAccess $access
    ): void {
        Gate::authorize('my-reports.view');

        $letter->load([
            'letterType',
            'activityType',
            'personnels.unit',
            'sptReport.creator',
            'sptReport.submitter',
        ]);

        abort_unless($letter->letterType?->code === 'SPT', 404);
        abort_unless($access->canAccess(auth()->user(), $letter), 404);
        abort_unless($letter->requiresSptReport(), 404);

        $this->letter = $letter;
        $this->report = $letter->sptReport;

        if ($this->report) {
            $this->fillFromReport($this->report);
        }
    }

    public function saveDraft(): void
    {
        $this->persist(false);
    }

    public function submit(): void
    {
        $this->persist(true);
    }

    private function persist(bool $submit): void
    {
        Gate::authorize('my-reports.view');

        if (! $this->letter->requiresSptReport()) {
            throw ValidationException::withMessages([
                'report' => 'SPT ini belum dapat dilaporkan.',
            ]);
        }

        $data = $this->validate([
            'activity_summary' => ['required', 'string', 'min:10', 'max:5000'],
            'results' => ['required', 'string', 'min:10', 'max:5000'],
            'obstacles' => ['nullable', 'string', 'max:5000'],
            'follow_up' => ['nullable', 'string', 'max:5000'],
        ]);

        $report = DB::transaction(function () use ($data, $submit) {
            $report = SptReport::query()
                ->where('letter_id', $this->letter->id)
                ->lockForUpdate()
                ->first();

            if ($report && $report->isSubmitted()) {
                throw ValidationException::withMessages([
                    'report' => 'Laporan SKP sudah dikirim dan tidak dapat diubah.',
                ]);
            }

            if ($report && $report->created_by !== auth()->id()) {
                throw ValidationException::withMessages([
                    'report' => 'Draft laporan sedang disusun oleh peserta lain.',
                ]);
            }

            if (! $report) {
                $report = new SptReport([
                    'letter_id' => $this->letter->id,
                    'status' => SptReport::STATUS_DRAFT,
                    'created_by' => auth()->id(),
                ]);
            }

            $report->fill([
                ...$data,
                'updated_by' => auth()->id(),
            ]);

            if ($submit) {
                $report->status = SptReport::STATUS_SUBMITTED;
                $report->submitted_by = auth()->id();
                $report->submitted_at = now();
            }

            $report->save();

            return $report->fresh(['creator', 'submitter']);
        });

        $this->report = $report;
        $this->fillFromReport($report);

        session()->flash(
            'success',
            $submit
                ? 'Laporan SKP berhasil dikirim untuk seluruh peserta SPT.'
                : 'Draft laporan SKP berhasil disimpan.'
        );
    }

    private function fillFromReport(SptReport $report): void
    {
        $this->activity_summary = $report->activity_summary;
        $this->results = $report->results;
        $this->obstacles = $report->obstacles ?? '';
        $this->follow_up = $report->follow_up ?? '';
    }

    public function getCanEditProperty(): bool
    {
        return ! $this->report
            || (
                ! $this->report->isSubmitted()
                && $this->report->created_by === auth()->id()
            );
    }

    public function render()
    {
        return view('livewire.my-spt.report');
    }
}
