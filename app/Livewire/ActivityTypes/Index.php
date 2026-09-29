<?php

use App\Models\ActivityType;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'active';

    public function mount(): void
    {
        Gate::authorize('activity-types.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deactivate(int $id): void
    {
        Gate::authorize('activity-types.manage');

        ActivityType::findOrFail($id)->update([
            'is_active' => false,
        ]);
    }

    public function activate(int $id): void
    {
        Gate::authorize('activity-types.manage');

        ActivityType::findOrFail($id)->update([
            'is_active' => true,
        ]);
    }

    public function with(): array
    {
        return [
            'activityTypes' => ActivityType::query()
                ->when(
                    filled($this->search),
                    function ($query) {
                        $search = trim($this->search);

                        $query->where(function ($query) use ($search) {
                            $query
                                ->where('name', 'ilike', "%{$search}%")
                                ->orWhere('code', 'ilike', "%{$search}%");
                        });
                    }
                )
                ->when(
                    $this->status === 'active',
                    fn ($query) => $query->where('is_active', true)
                )
                ->when(
                    $this->status === 'inactive',
                    fn ($query) => $query->where('is_active', false)
                )
                ->orderBy('name')
                ->paginate(15),
        ];
    }
};