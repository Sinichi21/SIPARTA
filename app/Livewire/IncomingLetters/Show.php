<?php

namespace App\Livewire\IncomingLetters;

use App\Models\IncomingLetter;
use App\Services\IncomingLetterService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Show extends Component
{
    public IncomingLetter $letter;

    public function mount(IncomingLetter $letter): void
    {
        Gate::authorize('incoming-letters.view');
        $this->letter = $letter->load(['creator','updater']);
    }

    public function dispose(IncomingLetterService $service): void
    {
        Gate::authorize('incoming-letters.process');
        $service->dispose($this->letter, auth()->user());
        $this->letter->refresh();
    }

    public function process(IncomingLetterService $service): void
    {
        Gate::authorize('incoming-letters.process');
        $service->process($this->letter, auth()->user());
        $this->letter->refresh();
    }

    public function complete(IncomingLetterService $service): void
    {
        Gate::authorize('incoming-letters.process');
        $service->complete($this->letter, auth()->user());
        $this->letter->refresh();
    }

    public function archive(IncomingLetterService $service): void
    {
        Gate::authorize('incoming-letters.archive');
        $service->archive($this->letter, auth()->user());
        $this->letter->refresh();
    }

    public function downloadOriginal()
    {
        Gate::authorize('incoming-letters.view');

        abort_unless(
            $this->letter->original_file_path
            && Storage::exists($this->letter->original_file_path),
            404
        );

        return Storage::download(
            $this->letter->original_file_path,
            $this->letter->original_file_name ?: 'surat-masuk'
        );
    }

    public function render()
    {
        return view('livewire.incoming-letters.show');
    }
}
