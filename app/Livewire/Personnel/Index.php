<?php

namespace App\Livewire\Personnel;

use App\Models\Personnel;
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
        Gate::authorize('personnels.view');
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
        Gate::authorize('personnels.update');

        $personnel = Personnel::findOrFail($id);

        $old = $personnel->getOriginal();

        $personnel->update([
            'is_active' => false,
        ]);

        $audit->updated(
            $personnel,
            $old
        );

        session()->flash(
            'success',
            'Personil berhasil dinonaktifkan.'
        );
    }

    public function activate(
        int $id,
        AuditService $audit
    ): void {
        Gate::authorize('personnels.update');

        $personnel = Personnel::findOrFail($id);

        $old = $personnel->getOriginal();

        $personnel->update([
            'is_active' => true,
        ]);

        $audit->updated(
            $personnel,
            $old
        );

        session()->flash(
            'success',
            'Personil berhasil diaktifkan.'
        );
    }

    public function render()
    {
        $personnels = Personnel::query()
            ->with('unit')
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
                                    'nip',
                                    'ilike',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'position',
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
            'livewire.personnel.index',
            compact('personnels')
        );
    }
}