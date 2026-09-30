<?php

namespace App\Livewire\IncomingLetters;

use App\Models\IncomingLetter;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    public IncomingLetter $letter;

    public string $agenda_number = '';
    public string $number = '';
    public string $letter_date = '';
    public string $received_date = '';
    public string $sender = '';
    public string $subject = '';
    public string $classification = '';
    public string $nature = 'biasa';
    public string $attachment_note = '';
    public string $destination = '';
    public string $notes = '';

    public function mount(IncomingLetter $letter): void
    {
        Gate::authorize('incoming-letters.update');
        abort_unless($letter->canBeEdited(), 403);

        $this->letter = $letter;

        foreach ([
            'agenda_number',
            'number',
            'sender',
            'subject',
            'classification',
            'nature',
            'attachment_note',
            'destination',
            'notes',
        ] as $field) {
            $this->{$field} = (string) ($letter->{$field} ?? '');
        }

        $this->letter_date = $letter->letter_date?->toDateString() ?? '';
        $this->received_date = $letter->received_date?->toDateString() ?? '';
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('incoming-letters.update');
        abort_unless($this->letter->canBeEdited(), 403);

        $data = $this->validate([
            'agenda_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('incoming_letters', 'agenda_number')
                    ->ignore($this->letter->id),
            ],
            'number' => ['nullable', 'string', 'max:255'],
            'letter_date' => ['nullable', 'date'],
            'received_date' => ['required', 'date'],
            'sender' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:2000'],
            'classification' => ['nullable', 'string', 'max:255'],
            'nature' => ['required', 'in:biasa,segera,penting,rahasia'],
            'attachment_note' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $old = $this->letter->getOriginal();

        $this->letter->update(array_merge(
            $data,
            ['updated_by' => auth()->id()]
        ));

        $audit->updated($this->letter, $old);

        session()->flash('success', 'Surat masuk berhasil diperbarui.');

        return $this->redirectRoute(
            'incoming-letters.show',
            $this->letter,
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.incoming-letters.edit');
    }
}
