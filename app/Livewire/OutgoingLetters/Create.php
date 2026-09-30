<?php

namespace App\Livewire\OutgoingLetters;

use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\LetterheadProfile;
use App\Models\OutgoingLetter;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Create extends Component
{
    public ?int $letter_type_id = null;
    public ?int $letter_template_id = null;
    public ?int $letterhead_profile_id = null;
    public string $recipient = '';
    public string $subject = '';
    public string $classification = '';
    public string $nature = 'biasa';
    public string $content_html = '';
    public string $notes = '';

    public function mount(): void
    {
        Gate::authorize('outgoing-letters.create');

        $this->letterhead_profile_id = LetterheadProfile::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->value('id');
    }

    public function updatedLetterTemplateId(): void
    {
        if (! $this->letter_template_id) {
            return;
        }

        $template = LetterTemplate::query()
            ->where('is_active', true)
            ->find($this->letter_template_id);

        if ($template) {
            $this->letter_type_id = $template->letter_type_id;
            $this->content_html = $template->content_html ?? '';

            if ($template->letterhead_profile_id) {
                $this->letterhead_profile_id =
                    $template->letterhead_profile_id;
            }
        }
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('outgoing-letters.create');

        $data = $this->validate([
            'letter_type_id' => ['nullable', 'integer', 'exists:letter_types,id'],
            'letter_template_id' => ['nullable', 'integer', 'exists:letter_templates,id'],
            'letterhead_profile_id' => ['nullable', 'integer', 'exists:letterhead_profiles,id'],
            'recipient' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:2000'],
            'classification' => ['nullable', 'string', 'max:255'],
            'nature' => ['required', 'in:biasa,segera,penting,rahasia'],
            'content_html' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $letter = OutgoingLetter::create(array_merge(
            $data,
            [
                'status' => 'draft',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        ));

        $audit->created($letter);

        session()->flash('success', 'Draft surat keluar berhasil dibuat.');

        return $this->redirectRoute(
            'outgoing-letters.show',
            $letter,
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.outgoing-letters.create', [
            'letterTypes' => LetterType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'templates' => LetterTemplate::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'letterheads' => LetterheadProfile::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
