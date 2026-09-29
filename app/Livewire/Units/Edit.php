<?php

namespace App\Livewire\Units;

use App\Models\Unit;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    public Unit $unit;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    public function mount(Unit $unit): void
    {
        Gate::authorize('units.manage');

        $this->unit = $unit;

        $this->code = $unit->code ?? '';
        $this->name = $unit->name;
        $this->description =
            $unit->description ?? '';
    }

    protected function rules(): array
    {
        return [
            'code' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique(
                    'units',
                    'code'
                )->ignore($this->unit->id),
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
        ];
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('units.manage');

        $data = $this->validate();

        $oldValues =
            $this->unit->getOriginal();

        $data['code'] = filled($data['code'])
            ? strtoupper(trim($data['code']))
            : null;

        $data['name'] = trim($data['name']);

        $data['description'] =
            filled($data['description'])
                ? trim($data['description'])
                : null;

        $this->unit->update($data);

        $audit->updated(
            $this->unit,
            $oldValues
        );

        session()->flash(
            'success',
            'Unit berhasil diperbarui.'
        );

        return $this->redirectRoute(
            'units.index',
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.units.edit');
    }
}