<?php

namespace App\Livewire\Letters;

use App\Enums\LetterRecordType;
use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\Personnel;
use App\Models\PersonnelTeam;
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
    public string $assignment_purpose = '';
    public string $departure_place = '';
    public string $destination_place = '';
    public string $transport_mode = '';
    public string $budget_account = '';
    public string $description = '';
    public string $record_type = 'normal';
    public string $personnel_scope = Letter::PERSONNEL_SCOPE_SELECTED;
    public array $personnel_ids = [];
    public ?int $personnel_team_id = null;
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
        $this->personnel_team_id = null;
        $this->resetValidation([
            'personnel_scope',
            'personnel_ids',
        ]);
    }

    public function useTeamPersonnelScope(): void
    {
        Gate::authorize('letters.create');

        $this->personnel_scope = Letter::PERSONNEL_SCOPE_TEAM;
        $this->personnel_ids = [];
        $this->resetValidation([
            'personnel_scope',
            'personnel_team_id',
            'personnel_ids',
        ]);
    }

    public function updatedPersonnelTeamId(): void
    {
        if ($this->personnel_scope !== Letter::PERSONNEL_SCOPE_TEAM || ! $this->personnel_team_id) {
            return;
        }

        $this->personnel_ids = PersonnelTeam::query()
            ->whereKey($this->personnel_team_id)
            ->where('is_active', true)
            ->first()?->personnels()
            ->where('is_active', true)
            ->pluck('personnels.id')
            ->map(fn ($id) => (int) $id)
            ->all() ?? [];
    }

    public function useAllPersonnelScope(): void
    {
        Gate::authorize('letters.create');

        $this->personnel_scope = Letter::PERSONNEL_SCOPE_ALL;
        $this->personnel_team_id = null;
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
            'assignment_purpose' => ['nullable', 'string', 'max:3000'],
            'departure_place' => ['nullable', 'string', 'max:500'],
            'destination_place' => ['nullable', 'string', 'max:500'],
            'transport_mode' => ['nullable', 'string', 'max:180'],
            'budget_account' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'record_type' => ['required', 'in:normal,attendance_correction'],
            'personnel_scope' => ['required', 'in:selected,team,all'],
            'personnel_team_id' => $this->personnel_scope === Letter::PERSONNEL_SCOPE_TEAM
                ? ['required', 'integer', 'exists:personnel_teams,id']
                : ['nullable'],
            'personnel_ids' => $this->personnel_scope === Letter::PERSONNEL_SCOPE_SELECTED
                ? ['required', 'array', 'min:1']
                : ['array'],
            'personnel_ids.*' => [
                'integer',
                'distinct',
                'exists:personnels,id',
            ],
        ]);

        if ($data['personnel_scope'] !== Letter::PERSONNEL_SCOPE_SELECTED) {
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

            'personnelTeams' => PersonnelTeam::query()
                ->where('is_active', true)
                ->withCount(['personnels' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
