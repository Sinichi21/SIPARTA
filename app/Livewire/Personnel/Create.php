<?php

namespace App\Livewire\Personnel;

use App\Models\Personnel;
use App\Models\Unit;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Create extends Component
{
    public ?int $unit_id = null;

    public string $nip = '';

    public string $name = '';

    public string $rank = '';

    public string $grade = '';

    public string $position = '';

    public string $email = '';

    public string $phone = '';

    public function mount(): void
    {
        Gate::authorize('personnels.create');
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
                'unique:personnels,nip',
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
        Gate::authorize('personnels.create');

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
        $data['is_active'] = true;

        $personnel = Personnel::create($data);

        $audit->created($personnel);

        session()->flash(
            'success',
            'Personil berhasil ditambahkan.'
        );

        return $this->redirectRoute(
            'personnels.index',
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.personnel.create', [
            'units' => Unit::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }
}