<?php

namespace App\Livewire\LetterTemplates;

use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Services\LetterTemplateRenderer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    public LetterTemplate $letterTemplate;

    public string $name = '';
    public string $code = '';
    public ?int $letter_type_id = null;
    public string $content_html = '';
    public bool $is_default = false;
    public bool $is_active = true;

    public ?int $previewLetterId = null;

    public function mount(
        LetterTemplate $letterTemplate
    ): void {
        Gate::authorize('settings.manage');

        $this->letterTemplate = $letterTemplate;

        $this->name = $letterTemplate->name;
        $this->code = $letterTemplate->code;
        $this->letter_type_id = $letterTemplate->letter_type_id;
        $this->content_html = $letterTemplate->content_html;
        $this->is_default = $letterTemplate->is_default;
        $this->is_active = $letterTemplate->is_active;
    }

    public function save(): void
    {
        Gate::authorize('settings.manage');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique(
                    'letter_templates',
                    'code'
                )->ignore($this->letterTemplate->id),
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

        DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                LetterTemplate::query()
                    ->where('letter_type_id', $data['letter_type_id'])
                    ->whereKeyNot($this->letterTemplate->id)
                    ->update(['is_default' => false]);
            }

            $contentChanged =
                $this->letterTemplate->content_html
                !== $data['content_html'];

            $this->letterTemplate->update([
                ...$data,
                'version' => $contentChanged
                    ? $this->letterTemplate->version + 1
                    : $this->letterTemplate->version,
                'updated_by' => auth()->id(),
            ]);

            $this->letterTemplate->refresh();
        });

        session()->flash(
            'success',
            'Template surat berhasil diperbarui.'
        );
    }

    public function render(
        LetterTemplateRenderer $renderer
    ) {
        $previewLetter = null;
        $renderedPreview = null;

        if ($this->previewLetterId) {
            $previewLetter = Letter::query()
                ->spt()
                ->with([
                    'activityType',
                    'personnels.unit',
                ])
                ->find($this->previewLetterId);

            if ($previewLetter) {
                $previewTemplate =
                    $this->letterTemplate->replicate();

                $previewTemplate->content_html =
                    $this->content_html;

                $renderedPreview = $renderer->render(
                    $previewTemplate,
                    $previewLetter
                );
            }
        }

        return view(
            'livewire.letter-templates.edit',
            [
                'letterTypes' => LetterType::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(),

                'previewLetters' => Letter::query()
                    ->spt()
                    ->orderByDesc('letter_date')
                    ->orderByDesc('id')
                    ->limit(25)
                    ->get([
                        'id',
                        'number',
                        'subject',
                        'letter_date',
                    ]),

                'previewLetter' => $previewLetter,
                'renderedPreview' => $renderedPreview,
            ]
        );
    }
}
