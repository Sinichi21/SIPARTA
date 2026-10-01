<?php

namespace App\Livewire\PersonnelTeams;

use App\Models\Personnel;
use App\Models\PersonnelTeam;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public bool $is_active = true;
    public array $personnel_ids = [];

    public function mount(): void
    {
        Gate::authorize('personnels.view');
    }

    public function edit(int $id): void
    {
        Gate::authorize('personnels.update');

        $team = PersonnelTeam::query()->with('personnels')->findOrFail($id);
        $this->editingId = $team->id;
        $this->name = $team->name;
        $this->code = $team->code ?? '';
        $this->description = $team->description ?? '';
        $this->is_active = $team->is_active;
        $this->personnel_ids = $team->personnels->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function save(): void
    {
        Gate::authorize('personnels.update');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('personnel_teams', 'name')->ignore($this->editingId)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('personnel_teams', 'code')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'personnel_ids' => ['array'],
            'personnel_ids.*' => ['integer', 'distinct', 'exists:personnels,id'],
        ]);

        $team = PersonnelTeam::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => trim($data['name']),
                'code' => filled($data['code']) ? strtoupper(trim($data['code'])) : null,
                'description' => filled($data['description']) ? trim($data['description']) : null,
                'is_active' => $data['is_active'],
            ]
        );

        $team->personnels()->sync(array_map('intval', $data['personnel_ids'] ?? []));

        $this->resetForm();
        session()->flash('success', 'Tim personil berhasil disimpan.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->is_active = true;
        $this->personnel_ids = [];
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.personnel-teams.index', [
            'teams' => PersonnelTeam::query()->withCount('personnels')->orderBy('name')->get(),
            'personnels' => Personnel::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
