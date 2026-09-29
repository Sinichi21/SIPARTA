<?php

namespace App\Livewire\Units;

use App\Models\Unit;
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
        Gate::authorize('units.manage');
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('units.manage');

        $data = $this->validate([
            'code' => [
                'nullable',
                'string',
                'max:50',
                'unique:units,code',
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

        $unit = Unit::create($data);

        $audit->created($unit);

        session()->flash(
            'success',
            'Unit berhasil ditambahkan.'
        );

        return $this->redirectRoute(
            'units.index',
            navigate: true
        );
    }
    public function render()
    {
        return view('livewire.units.create');
    }
}
