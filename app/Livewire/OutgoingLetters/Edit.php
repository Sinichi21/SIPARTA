<?php

namespace App\Livewire\OutgoingLetters;

use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\LetterheadProfile;
use App\Models\OutgoingLetter;
use App\Services\AuditService;
use App\Services\OutgoingLetterTemplateRenderer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Edit extends Component
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
    public array $manualFields = [];
    public array $manualPlaceholderNames = [];

    public string $numbering_mode = 'auto';
    public string $manual_number = '';
    public string $date_mode = 'auto';
    public string $manual_letter_date = '';
    public bool $showPreview = false;

    public OutgoingLetter $letter;

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
        $this->manualFields = is_array($letter->placeholder_data) ? $letter->placeholder_data : [];
        $this->numbering_mode = $letter->numbering_mode ?: 'auto';
        $this->manual_number = (string) ($letter->manual_number ?? '');
        $this->date_mode = $letter->date_mode ?: 'auto';
        $this->manual_letter_date = $letter->manual_letter_date?->toDateString() ?? now()->toDateString();
        $this->syncManualPlaceholders();
    }

    public function updatedLetterTemplateId(): void
    {
        if (! $this->letter_template_id) {
            $this->content_html = '';
            $this->manualFields = [];
            $this->manualPlaceholderNames = [];
            $this->showPreview = false;
            return;
        }

        $template = LetterTemplate::query()
            ->where('is_active', true)
            ->find($this->letter_template_id);

        if (! $template) {
            return;
        }

        $this->letter_type_id = $template->letter_type_id;
        $this->content_html = $template->content_html ?? '';

        if ($template->letterhead_profile_id) {
            $this->letterhead_profile_id = $template->letterhead_profile_id;
        }

        $this->syncManualPlaceholders();
        $this->showPreview = true;
    }

    public function updatedContentHtml(): void
    {
        $this->syncManualPlaceholders();
    }

    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('outgoing-letters.update');
        abort_unless($this->letter->canBeEdited(), 403);

        $data = $this->validate([
            'letter_type_id' => ['nullable','integer','exists:letter_types,id'],
            'letter_template_id' => ['nullable','integer','exists:letter_templates,id'],
            'letterhead_profile_id' => ['nullable','integer','exists:letterhead_profiles,id'],
            'recipient' => ['required','string','max:255'],
            'subject' => ['required','string','max:2000'],
            'classification' => ['nullable','string','max:255'],
            'nature' => ['required','in:biasa,segera,penting,rahasia'],
            'content_html' => ['nullable','string'],
            'notes' => ['nullable','string','max:5000'],
            'manualFields' => ['array'],
            'manualFields.*' => ['nullable','string','max:5000'],
            'numbering_mode' => ['required','in:auto,manual'],
            'manual_number' => ['nullable','string','max:255'],
            'date_mode' => ['required','in:auto,manual'],
            'manual_letter_date' => ['nullable','date'],
        ]);

        $this->validateManualCandidate();

        $old = $this->letter->getOriginal();

        $this->letter->update([
            ...collect($data)->except('manualFields')->all(),
            'placeholder_data' => $this->cleanManualFields(),
            'number' => null,
            'letter_date' => null,
            'updated_by' => auth()->id(),
        ]);

        $audit->updated($this->letter, $old);

        session()->flash(
            'success',
            'Draft surat keluar berhasil diperbarui. Nomor resmi belum dialokasikan.'
        );

        return $this->redirectRoute(
            'outgoing-letters.show',
            $this->letter,
            navigate: true
        );
    }

    public function render(OutgoingLetterTemplateRenderer $renderer)
    {
        $previewLetter = $this->previewLetter();
        $previewBody = $renderer->render(
            $previewLetter,
            true,
            true
        );

        return view('livewire.outgoing-letters.edit', [
            'letterTypes' => LetterType::query()->where('is_active', true)->orderBy('name')->get(),
            'templates' => LetterTemplate::query()->where('is_active', true)->orderBy('name')->get(),
            'letterheads' => LetterheadProfile::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'previewLetter' => $previewLetter,
            'previewBody' => $previewBody,
            'missingPlaceholders' => $renderer->missingPlaceholders($previewLetter),
        ]);
    }

    private function previewLetter(): OutgoingLetter
    {
        $letter = new OutgoingLetter([
            'letter_type_id' => $this->letter_type_id,
            'letter_template_id' => $this->letter_template_id,
            'letterhead_profile_id' => $this->letterhead_profile_id,
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'classification' => $this->classification,
            'nature' => $this->nature,
            'content_html' => $this->content_html,
            'placeholder_data' => $this->cleanManualFields(),
            'numbering_mode' => $this->numbering_mode,
            'manual_number' => $this->manual_number,
            'date_mode' => $this->date_mode,
            'manual_letter_date' => $this->manual_letter_date ?: null,
        ]);

        if ($this->letterhead_profile_id) {
            $letter->setRelation(
                'letterheadProfile',
                LetterheadProfile::query()->find($this->letterhead_profile_id)
            );
        }

        return $letter;
    }

    private function validateManualCandidate(): void
    {
        if ($this->numbering_mode === 'manual') {
            $number = trim($this->manual_number);

            if ($number === '') {
                throw ValidationException::withMessages([
                    'manual_number' => 'Nomor manual wajib diisi.',
                ]);
            }

            if (OutgoingLetter::query()->where('number', $number)->whereKeyNot($this->letter->id)->exists()) {
                throw ValidationException::withMessages([
                    'manual_number' => 'Nomor surat sudah digunakan oleh surat terbit lain.',
                ]);
            }
        }

        if ($this->date_mode === 'manual' && blank($this->manual_letter_date)) {
            throw ValidationException::withMessages([
                'manual_letter_date' => 'Tanggal manual wajib diisi.',
            ]);
        }
    }

    private function syncManualPlaceholders(): void
    {
        $renderer = app(OutgoingLetterTemplateRenderer::class);
        $this->manualPlaceholderNames = $renderer->manualPlaceholders($this->content_html);

        $old = $this->manualFields;
        $this->manualFields = collect($this->manualPlaceholderNames)
            ->mapWithKeys(fn ($name) => [$name => $old[$name] ?? ''])
            ->all();
    }

    private function cleanManualFields(): array
    {
        return collect($this->manualFields)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->all();
    }
}
