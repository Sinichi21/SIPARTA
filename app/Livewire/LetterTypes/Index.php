<?php

use App\Models\LetterType;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('letter-types.view');
    }

    public function toggle(int $id): void
    {
        Gate::authorize('letter-types.manage');

        $type = LetterType::findOrFail($id);

        $type->update([
            'is_active' => ! $type->is_active,
        ]);
    }

    public function with(): array
    {
        return [
            'letterTypes' => LetterType::query()
                ->when(
                    filled($this->search),
                    function ($query) {
                        $search = trim($this->search);

                        $query->where(function ($query) use ($search) {
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
                        });
                    }
                )
                ->orderBy('name')
                ->paginate(15),
        ];
    }
};