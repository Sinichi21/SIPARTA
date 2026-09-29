<?php

namespace App\Livewire\Letters;

use App\Models\ActivityType;
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
            'personnel_ids' => ['required', 'array', 'min:1'],
            'personnel_ids.*' => [
                'integer',
                'distinct',
                'exists:personnels,id',
            ],
        ]);

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

    public function render()
    {
        return view('livewire.letters.create', [
            'activityTypes' => ActivityType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'personnels' => Personnel::query()
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
                ->orderBy('name')
                ->limit(100)
                ->get(),
        ]);
    }
}
