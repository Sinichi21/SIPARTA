<?php

namespace App\Livewire\Personnel;

use App\Models\Personnel;
use App\Models\Unit;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    public Personnel $personnel;

    public ?int $unit_id = null;

    public string $nip = '';

    public string $name = '';

    public string $rank = '';

    public string $grade = '';

    public string $position = '';

    public string $email = '';

    public string $phone = '';

    public function mount(
        Personnel $personnel
    ): void {
        Gate::authorize('personnels.update');

        $this->personnel = $personnel;

        $this->unit_id = $personnel->unit_id;
        $this->nip = $personnel->nip ?? '';
        $this->name = $personnel->name;
        $this->rank = $personnel->rank ?? '';
        $this->grade = $personnel->grade ?? '';
        $this->position = $personnel->position ?? '';
        $this->email = $personnel->email ?? '';
        $this->phone = $personnel->phone ?? '';
    }

    protected function rules(): array
    {
        return [
            'unit_id' => [
                'nullable',
                'exists:units,id',
            ],

            'nip' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique(
                    'personnels',
                    'nip'
                )->ignore(
                    $this->personnel->id
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'rank' => [
                'nullable',
                'string',
                'max:100',
            ],

            'grade' => [
                'nullable',
                'string',
                'max:50',
            ],

            'position' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('personnels.update');

        $data = $this->validate();

        foreach ([
            'nip',
            'rank',
            'grade',
            'position',
            'email',
            'phone',
        ] as $field) {
            $data[$field] = filled($data[$field] ?? null)
                ? trim($data[$field])
                : null;
        }

        $data['name'] = trim($data['name']);

        $oldValues =
            $this->personnel->getOriginal();

        $this->personnel->update($data);

        $audit->updated(
            $this->personnel,
            $oldValues
        );

        session()->flash(
            'success',
            'Data personil berhasil diperbarui.'
        );

        return $this->redirectRoute(
            'personnels.index',
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.personnel.edit', [
            'units' => Unit::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }
}