<?php

namespace App\Livewire\ActivityTypes;

use App\Models\ActivityType;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Create extends Component
{
    public string $code = '';
    public string $name = '';
    public string $description = '';

    public function mount(): void
    {
        Gate::authorize('activity-types.manage');
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('activity-types.manage');

        $data = $this->validate([
            'code' => [
                'nullable',
                'string',
                'max:50',
                'unique:activity_types,code',
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

        $data['code'] = filled($data['code'])
            ? strtoupper(trim($data['code']))
            : null;

        $data['name'] = trim($data['name']);

        $data['description'] = filled($data['description'])
            ? trim($data['description'])
            : null;

        $data['is_active'] = true;

        $activityType = ActivityType::create($data);

        $audit->created($activityType);

        session()->flash(
            'success',
            'Jenis kegiatan berhasil ditambahkan.'
        );

        return $this->redirectRoute(
            'activity-types.index',
            navigate: true
        );
    }
    public function render()
    {
        return view('livewire.activity-types.create');
    }
}
