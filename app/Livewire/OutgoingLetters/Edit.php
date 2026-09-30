<?php

namespace App\Livewire\OutgoingLetters;

use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\LetterheadProfile;
use App\Models\OutgoingLetter;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Edit extends Component
{
    public OutgoingLetter $letter;

    public ?int $letter_type_id = null;
    public ?int $letter_template_id = null;
    public ?int $letterhead_profile_id = null;
    public string $recipient = '';
    public string $subject = '';
    public string $classification = '';
    public string $nature = 'biasa';
    public string $content_html = '';
    public string $notes = '';

    public function mount(OutgoingLetter $letter): void
    {
        Gate::authorize('outgoing-letters.update');
        abort_unless($letter->canBeEdited(), 403);

        $this->letter = $letter;
        $this->letter_type_id = $letter->letter_type_id;
        $this->letter_template_id = $letter->letter_template_id;
        $this->letterhead_profile_id = $letter->letterhead_profile_id;
        $this->recipient = $letter->recipient;
        $this->subject = $letter->subject;
        $this->classification = (string) ($letter->classification ?? '');
        $this->nature = $letter->nature;
        $this->content_html = (string) ($letter->content_html ?? '');
        $this->notes = (string) ($letter->notes ?? '');
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('outgoing-letters.update');
        abort_unless($this->letter->canBeEdited(), 403);

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

        $old = $this->letter->getOriginal();

        $this->letter->update(array_merge(
            $data,
            ['updated_by' => auth()->id()]
        ));

        $audit->updated($this->letter, $old);

        session()->flash('success', 'Draft surat keluar berhasil diperbarui.');

        return $this->redirectRoute(
            'outgoing-letters.show',
            $this->letter,
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.outgoing-letters.edit', [
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
