<?php

use App\Models\AuditLog;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $action = '';

    public function mount(): void
    {
        Gate::authorize('audit-logs.view');
    }

    public function with(): array
    {
        return [
            'logs' => AuditLog::query()
                ->with('user')
                ->when(
                    filled($this->action),
                    fn ($query) =>
                        $query->where(
                            'action',
                            strtoupper($this->action)
                        )
                )
                ->when(
                    filled($this->search),
                    function ($query) {
                        $search = trim($this->search);

                        $query->where(function ($query) use ($search) {
                            $query
                                ->where('subject_type', 'ilike', "%{$search}%")
                                ->orWhereHas(
                                    'user',
                                    fn ($userQuery) =>
                                        $userQuery->where(
                                            'name',
                                            'ilike',
                                            "%{$search}%"
                                        )
                                );
                        });
                    }
                )
                ->latest('created_at')
                ->paginate(25),
        ];
    }
};