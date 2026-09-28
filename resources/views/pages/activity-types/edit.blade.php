<?php

use App\Models\ActivityType;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public ActivityType $activityType;

    public string $code = '';
    public string $name = '';
    public string $description = '';

    public function mount(
        ActivityType $activityType
    ): void {
        Gate::authorize('activity-types.manage');

        $this->activityType = $activityType;

        $this->code = $activityType->code ?? '';
        $this->name = $activityType->name;
        $this->description = $activityType->description ?? '';
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('activity-types.manage');

        $data = $this->validate([
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(
                    'activity_types',
                    'code'
                )->ignore(
                    $this->activityType->id
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $old = $this->activityType->getOriginal();

        $data['code'] = filled($data['code'])
            ? strtoupper(trim($data['code']))
            : null;

        $data['name'] = trim($data['name']);

        $data['description'] = filled($data['description'])
            ? trim($data['description'])
            : null;

        $this->activityType->update($data);

        $audit->updated(
            $this->activityType,
            $old
        );

        session()->flash(
            'success',
            'Jenis kegiatan berhasil diperbarui.'
        );

        return $this->redirectRoute(
            'activity-types.index',
            navigate: true
        );
    }
};
?>

<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold">
            Edit Jenis Kegiatan
        </h1>
    </div>

    <form
        wire:submit="save"
        class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
    >
        <div>
            <label class="mb-1 block text-sm font-medium">
                Kode
            </label>

            <input
                wire:model="code"
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
            >
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">
                Nama *
            </label>

            <input
                wire:model="name"
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
            >
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">
                Deskripsi
            </label>

            <textarea
                wire:model="description"
                rows="4"
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
            ></textarea>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
            <a
                href="{{ route('activity-types.index') }}"
                wire:navigate
                class="rounded-lg border border-slate-300 px-4 py-2"
            >
                Batal
            </a>

            <button
                type="submit"
                class="rounded-lg bg-blue-700 px-5 py-2 font-semibold text-white"
            >
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>