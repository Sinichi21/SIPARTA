<?php

use App\Models\LetterType;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component {
    public string $code = '';
    public string $name = '';
    public string $description = '';

    public string $numbering_pattern =
        '{sequence}/{type}/{month_roman}/{year}';

    public bool $requires_personnel = false;

    public function mount(): void
    {
        Gate::authorize('letter-types.manage');
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('letter-types.manage');

        $data = $this->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:letter_types,code',
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

            'numbering_pattern' => [
                'nullable',
                'string',
                'max:255',
            ],

            'requires_personnel' => [
                'boolean',
            ],
        ]);

        $data['code'] = strtoupper(
            trim($data['code'])
        );

        $data['name'] = trim($data['name']);

        $data['is_active'] = true;

        $type = LetterType::create($data);

        $audit->created($type);

        session()->flash(
            'success',
            'Jenis surat berhasil ditambahkan.'
        );

        return $this->redirectRoute(
            'letter-types.index',
            navigate: true
        );
    }
};