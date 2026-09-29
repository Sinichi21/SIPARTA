<?php

namespace App\Livewire\LetterTemplates;

use App\Models\LetterTemplate;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('settings.view');
    }

    public function toggle(int $id): void
    {
        Gate::authorize('settings.manage');

        $template = LetterTemplate::query()->findOrFail($id);

        $template->update([
            'is_active' => ! $template->is_active,
            'updated_by' => auth()->id(),
        ]);
    }

    public function render()
    {
        return view('livewire.letter-templates.index', [
            'templates' => LetterTemplate::query()
                ->with('letterType')
                ->when(
                    filled($this->search),
                    function ($query) {
                        $search = trim($this->search);

                        $query->where(
                            fn ($query) => $query
                                ->where('name', 'ilike', "%{$search}%")
                                ->orWhere('code', 'ilike', "%{$search}%")
                        );
                    }
                )
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }
}
