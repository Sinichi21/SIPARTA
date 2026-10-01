<?php

namespace App\Livewire\NumberingSettings;

use App\Models\NumberingPlaceholder;
use App\Models\OutgoingLetterNumberSequence;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public int $sequenceYear;
    public int $lastNumber = 0;

    public ?int $editingId = null;
    public string $key = '';
    public string $label = '';
    public string $type = 'select';
    public string $optionsText = '';
    public bool $is_required = true;
    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize('settings.manage');
        $this->sequenceYear = (int) now()->year;
        $this->loadSequence();
    }

    public function updatedSequenceYear(): void
    {
        $this->loadSequence();
    }

    public function saveSequence(): void
    {
        Gate::authorize('settings.manage');

        $data = $this->validate([
            'sequenceYear' => ['required', 'integer', 'min:2000', 'max:2200'],
            'lastNumber' => ['required', 'integer', 'min:0'],
        ]);

        OutgoingLetterNumberSequence::query()->updateOrCreate(
            ['year' => $data['sequenceYear']],
            ['last_number' => $data['lastNumber']]
        );

        session()->flash('success', 'Nomor terakhir surat keluar berhasil diperbarui.');
    }

    public function editPlaceholder(int $id): void
    {
        Gate::authorize('settings.manage');

        $placeholder = NumberingPlaceholder::query()->findOrFail($id);
        $this->editingId = $placeholder->id;
        $this->key = $placeholder->key;
        $this->label = $placeholder->label;
        $this->type = $placeholder->type;
        $this->optionsText = implode("
", $placeholder->normalizedOptions());
        $this->is_required = $placeholder->is_required;
        $this->is_active = $placeholder->is_active;
    }

    public function savePlaceholder(): void
    {
        Gate::authorize('settings.manage');

        $data = $this->validate([
            'key' => [
                'required',
                'regex:/^[a-z][a-z0-9_]*$/',
                'max:80',
                Rule::unique('numbering_placeholders', 'key')->ignore($this->editingId),
            ],
            'label' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:select,text'],
            'optionsText' => ['nullable', 'string', 'max:5000'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $options = $data['type'] === 'select'
            ? collect(preg_split('/\r\n|\r|\n/', $data['optionsText'] ?? ''))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all()
            : [];

        if ($data['type'] === 'select' && count($options) < 1) {
            $this->addError('optionsText', 'Placeholder select harus memiliki minimal satu pilihan.');
            return;
        }

        NumberingPlaceholder::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'key' => strtolower(trim($data['key'])),
                'label' => trim($data['label']),
                'type' => $data['type'],
                'options' => $options,
                'is_required' => $data['is_required'],
                'is_active' => $data['is_active'],
            ]
        );

        $this->resetPlaceholderForm();
        session()->flash('success', 'Placeholder penomoran berhasil disimpan.');
    }

    public function resetPlaceholderForm(): void
    {
        $this->editingId = null;
        $this->key = '';
        $this->label = '';
        $this->type = 'select';
        $this->optionsText = '';
        $this->is_required = true;
        $this->is_active = true;
        $this->resetValidation();
    }

    private function loadSequence(): void
    {
        $this->lastNumber = (int) OutgoingLetterNumberSequence::query()
            ->where('year', $this->sequenceYear)
            ->value('last_number');
    }

    public function render()
    {
        return view('livewire.numbering-settings.index', [
            'placeholders' => NumberingPlaceholder::query()->orderBy('label')->get(),
        ]);
    }
}
