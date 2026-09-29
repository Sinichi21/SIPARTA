<?php

namespace App\Livewire\Letters;

use App\Enums\LetterRecordType;
use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\Personnel;
use App\Services\LetterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Edit extends Component
{
    public Letter $letter;

    public string $number = '';

    public string $subject = '';

    public ?int $activity_type_id = null;

    public string $letter_date = '';

    public string $start_date = '';

    public string $end_date = '';

    public string $location = '';

    public string $basis = '';

    public string $description = '';

    public string $record_type = 'normal';

    public array $personnel_ids = [];

    public string $personnelSearch = '';

    public function mount(Letter $letter): void
    {
        Gate::authorize('letters.update');

        abort_unless(
            $letter->letterType?->code === 'SPT',
            404
        );

        abort_unless(
            $letter->canBeEdited(),
            403,
            'Hanya SPT draft atau hasil import yang dapat diedit.'
        );

        $this->letter = $letter;
        $this->number = $letter->number ?? '';
        $this->subject = $letter->subject;
        $this->activity_type_id = $letter->activity_type_id;
        $this->letter_date = $letter->letter_date?->format('Y-m-d') ?? '';
        $this->start_date = $letter->start_date?->format('Y-m-d') ?? '';
        $this->end_date = $letter->end_date?->format('Y-m-d') ?? '';
        $this->location = $letter->location ?? '';
        $this->basis = $letter->basis ?? '';
        $this->description = $letter->description ?? '';
        $this->record_type = $letter->record_type?->value ?? LetterRecordType::Normal->value;

        $this->personnel_ids = $letter
            ->personnels()
            ->pluck('personnels.id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function save(LetterService $service)
    {
        Gate::authorize('letters.update');

        $data = $this->validate([
            'number' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'activity_type_id' => [
                'required',
                'integer',
                'exists:activity_types,id',
            ],
            'letter_date' => ['required', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            'location' => ['required', 'string', 'max:500'],
            'basis' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'record_type' => ['required', 'in:normal,attendance_correction'],
            'personnel_ids' => ['required', 'array', 'min:1'],
            'personnel_ids.*' => [
                'integer',
                'distinct',
                'exists:personnels,id',
            ],
        ]);

        $service->updateSpt(
            $this->letter,
            $data,
            Auth::id()
        );

        session()->flash(
            'success',
            'SPT berhasil diperbarui.'
        );

        return $this->redirectRoute(
            'letters.show',
            ['letter' => $this->letter->id],
            navigate: true
        );
    }

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
}
