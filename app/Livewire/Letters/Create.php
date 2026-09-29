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

class Create extends Component
{
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
    public string $personnel_scope = Letter::PERSONNEL_SCOPE_SELECTED;
    public array $personnel_ids = [];
    public string $personnelSearch = '';

    public function mount(): void
    {
        Gate::authorize('letters.create');

        $today = now()->format('Y-m-d');

        $this->letter_date = $today;
        $this->start_date = $today;
        $this->end_date = $today;
    }

    public function useSelectedPersonnelScope(): void
    {
        Gate::authorize('letters.create');

        $this->personnel_scope = Letter::PERSONNEL_SCOPE_SELECTED;
        $this->resetValidation([
            'personnel_scope',
            'personnel_ids',
        ]);
    }

    public function useAllPersonnelScope(): void
    {
        Gate::authorize('letters.create');

        $this->personnel_scope = Letter::PERSONNEL_SCOPE_ALL;
        $this->personnel_ids = [];

        $this->resetValidation([
            'personnel_scope',
            'personnel_ids',
        ]);
    }

    public function selectAllPersonnel(): void
    {
        Gate::authorize('letters.create');

        $this->personnel_ids = Personnel::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->resetValidation('personnel_ids');
    }

    public function clearAllPersonnel(): void
    {
        Gate::authorize('letters.create');

        $this->personnel_ids = [];

        $this->resetValidation('personnel_ids');
    }

    public function selectVisiblePersonnel(): void
    {
        Gate::authorize('letters.create');

        $visibleIds = $this->personnelQuery()
            ->limit(100)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->personnel_ids = array_values(array_unique([
            ...$this->personnel_ids,
            ...$visibleIds,
        ]));

        $this->resetValidation('personnel_ids');
    }

    public function save(LetterService $service)
    {
        Gate::authorize('letters.create');

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
            'personnel_scope' => ['required', 'in:selected,all'],
            'personnel_ids' => $this->personnel_scope === Letter::PERSONNEL_SCOPE_SELECTED
                ? ['required', 'array', 'min:1']
                : ['array', 'max:0'],
            'personnel_ids.*' => [
                'integer',
                'distinct',
                'exists:personnels,id',
            ],
        ]);

        if (
            $data['personnel_scope']
            === Letter::PERSONNEL_SCOPE_ALL
        ) {
            $data['personnel_ids'] = [];
        }

        $letter = $service->createSpt($data, Auth::id());

        session()->flash(
            'success',
            'SPT berhasil dibuat sebagai draft.'
        );

        return $this->redirectRoute(
            'letters.show',
            ['letter' => $letter->id],
            navigate: true
        );
    }

    private function personnelQuery()
    {
        return Personnel::query()
            ->with('unit')
            ->where('is_active', true)
            ->when(
                filled($this->personnelSearch),
                fn ($query) => $query->where(
                    'name',
                    'ilike',
                    '%' . trim($this->personnelSearch) . '%'
                )
            )
            ->orderBy('name');
    }

    public function render()
    {
        return view('livewire.letters.create', [
            'activityTypes' => ActivityType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'personnels' => $this->personnelQuery()
                ->limit(100)
                ->get(),

            'totalActivePersonnel' => Personnel::query()
                ->where('is_active', true)
                ->count(),
        ]);
    }
}
