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
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">
            Audit Log
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Riwayat perubahan data dan aktivitas penting aplikasi.
        </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-[1fr_200px]">
            <input
                wire:model.live.debounce.300ms="search"
                placeholder="Cari pengguna atau objek..."
                class="rounded-lg border border-slate-300 px-3 py-2"
            >

            <select
                wire:model.live="action"
                class="rounded-lg border border-slate-300 px-3 py-2"
            >
                <option value="">Semua aksi</option>
                <option value="CREATE">Create</option>
                <option value="UPDATE">Update</option>
                <option value="PUBLISH">Publish</option>
                <option value="CANCEL">Cancel</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">
                            Waktu
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">
                            User
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">
                            Aksi
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">
                            Objek
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">
                            ID
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">
                            IP
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @foreach ($logs as $log)
                        <tr>
                            <td class="px-4 py-3 text-sm">
                                {{ $log->created_at?->format('d/m/Y H:i:s') }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $log->user?->name ?: 'System' }}
                            </td>

                            <td class="px-4 py-3 text-sm font-medium">
                                {{ $log->action }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ class_basename($log->subject_type ?: '-') }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $log->subject_id ?: '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $log->ip_address ?: '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
