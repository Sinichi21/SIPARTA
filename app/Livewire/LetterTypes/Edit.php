<?php

namespace App\Livewire\LetterTypes;

use App\Models\LetterType;
use App\Models\NumberingPlaceholder;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    public LetterType $letterType;

    public string $code = '';
    public string $name = '';
    public string $description = '';
    public string $numbering_pattern = '';
    public bool $requires_personnel = false;
    public bool $is_active = true;

    public function mount(LetterType $letterType): void
    {
        Gate::authorize('letter-types.manage');

        $this->letterType = $letterType;
        $this->code = $letterType->code;
        $this->name = $letterType->name;
        $this->description = $letterType->description ?? '';
        $this->numbering_pattern = $letterType->numbering_pattern ?? '';
        $this->requires_personnel = (bool) $letterType->requires_personnel;
        $this->is_active = (bool) $letterType->is_active;
    }

    protected function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('letter_types', 'code')
                    ->ignore($this->letterType->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'numbering_pattern' => ['nullable', 'string', 'max:255'],
            'requires_personnel' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('letter-types.manage');

        $data = $this->validate();
        $oldValues = $this->letterType->getOriginal();

        $data['code'] = strtoupper(trim($data['code']));
        $data['name'] = trim($data['name']);
        $data['description'] = filled($data['description'])
            ? trim($data['description'])
            : null;
        $data['numbering_pattern'] = filled($data['numbering_pattern'])
            ? trim($data['numbering_pattern'])
            : null;

        $this->letterType->update($data);
        $audit->updated($this->letterType, $oldValues);

        session()->flash(
            'success',
            'Jenis surat berhasil diperbarui.'
        );

        return $this->redirectRoute(
            'letter-types.index',
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.letter-types.edit', [
            'customPlaceholders' => NumberingPlaceholder::query()->where('is_active', true)->orderBy('label')->get(),
        ]);
    }
}
