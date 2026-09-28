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
?>

<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">
                Jenis Surat
            </flux:heading>

            <flux:text class="mt-1">
                Konfigurasi jenis dan format nomor surat.
            </flux:text>
        </div>

        @can('letter-types.manage')
            <flux:button
                variant="primary"
                href="{{ route('letter-types.create') }}"
                wire:navigate
            >
                Tambah Jenis Surat
            </flux:button>
        @endcan
    </div>

    <flux:card class="space-y-4">

        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Cari jenis surat..."
        />

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="px-3 py-3 text-left">
                            Kode
                        </th>
                        <th class="px-3 py-3 text-left">
                            Nama
                        </th>
                        <th class="px-3 py-3 text-left">
                            Format Nomor
                        </th>
                        <th class="px-3 py-3 text-left">
                            Status
                        </th>
                        <th class="px-3 py-3 text-right">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($letterTypes as $type)
                        <tr class="border-b">
                            <td class="px-3 py-3 font-medium">
                                {{ $type->code }}
                            </td>

                            <td class="px-3 py-3">
                                {{ $type->name }}
                            </td>

                            <td class="px-3 py-3 font-mono text-xs">
                                {{ $type->numbering_pattern ?: '-' }}
                            </td>

                            <td class="px-3 py-3">
                                {{ $type->is_active ? 'Aktif' : 'Nonaktif' }}
                            </td>

                            <td class="px-3 py-3 text-right">
                                @can('letter-types.manage')
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        href="{{ route('letter-types.edit', $type) }}"
                                        wire:navigate
                                    >
                                        Edit
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        wire:click="toggle({{ $type->id }})"
                                    >
                                        {{ $type->is_active
                                            ? 'Nonaktifkan'
                                            : 'Aktifkan' }}
                                    </flux:button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                class="px-3 py-10 text-center text-slate-500"
                            >
                                Belum ada jenis surat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $letterTypes->links() }}

    </flux:card>
</div>