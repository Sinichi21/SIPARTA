<?php

namespace App\Livewire\LetterTemplates;

use App\Models\LetterTemplate;
use App\Models\LetterType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';
    public string $code = '';
    public ?int $letter_type_id = null;
    public string $content_html = '';
    public bool $is_default = false;
    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize('settings.manage');

        $this->letter_type_id = LetterType::query()
            ->where('code', 'SPT')
            ->value('id');
    }

    public function save()
    {
        Gate::authorize('settings.manage');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('letter_templates', 'code'),
            ],
            'letter_type_id' => [
                'required',
                'integer',
                'exists:letter_types,id',
            ],
            'content_html' => [
                'required',
                'string',
                'max:1000000',
            ],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $template = DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                LetterTemplate::query()
                    ->where('letter_type_id', $data['letter_type_id'])
                    ->update(['is_default' => false]);
            }

            return LetterTemplate::create([
                ...$data,
                'version' => 1,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });

        session()->flash(
            'success',
            'Template surat berhasil dibuat.'
        );

        return $this->redirectRoute(
            'letter-templates.edit',
            ['letterTemplate' => $template->id],
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.letter-templates.create', [
            'letterTypes' => LetterType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
