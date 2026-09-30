<?php

namespace App\Livewire\IncomingLetters;

use App\Models\IncomingLetter;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Create extends Component
{
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

    public function mount(): void
    {
        Gate::authorize('incoming-letters.create');

        $this->received_date = now()->toDateString();
        $this->agenda_number = sprintf(
            'SM/%s/%04d',
            now()->format('Y'),
            ((int) IncomingLetter::query()->max('id')) + 1
        );
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('incoming-letters.create');

        $data = $this->validate([
            'agenda_number' => ['required', 'string', 'max:255', 'unique:incoming_letters,agenda_number'],
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

        $letter = IncomingLetter::create(array_merge(
            $data,
            [
                'status' => 'recorded',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        ));

        $audit->created($letter);

        session()->flash('success', 'Surat masuk berhasil dicatat.');

        return $this->redirectRoute(
            'incoming-letters.show',
            $letter,
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.incoming-letters.create');
    }
}
