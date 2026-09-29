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