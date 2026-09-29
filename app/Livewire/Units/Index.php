<?php

namespace App\Livewire\Units;

use App\Models\Unit;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'active';

    public function mount(): void
    {
        Gate::authorize('units.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function deactivate(
        int $id,
        AuditService $audit
    ): void {
        Gate::authorize('units.manage');

        $unit = Unit::findOrFail($id);

        $oldValues = $unit->getOriginal();

        $unit->update([
            'is_active' => false,
        ]);

        $audit->updated(
            $unit,
            $oldValues
        );

        session()->flash(
            'success',
            'Unit berhasil dinonaktifkan.'
        );
    }

    public function activate(
        int $id,
        AuditService $audit
    ): void {
        Gate::authorize('units.manage');

        $unit = Unit::findOrFail($id);

        $oldValues = $unit->getOriginal();

        $unit->update([
            'is_active' => true,
        ]);

        $audit->updated(
            $unit,
            $oldValues
        );

        session()->flash(
            'success',
            'Unit berhasil diaktifkan.'
        );
    }

    public function render()
    {
        $units = Unit::query()
            ->withCount('personnels')
            ->when(
                filled($this->search),
                function ($query) {
                    $search = trim($this->search);

                    $query->where(
                        function ($query) use ($search) {
                            $query
                                ->where(
                                    'name',
                                    'ilike',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'code',
                                    'ilike',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $this->status === 'active',
                fn ($query) =>
                    $query->where('is_active', true)
            )
            ->when(
                $this->status === 'inactive',
                fn ($query) =>
                    $query->where('is_active', false)
            )
            ->orderBy('name')
            ->paginate(15);

        return view(
            'livewire.units.index',
            compact('units')
        );
    }
}