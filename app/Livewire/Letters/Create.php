<?php

namespace App\Livewire\Letters;

use App\Models\ActivityType;
use App\Models\Personnel;
use App\Services\LetterService;
use Livewire\Component;

class Create extends Component
{
    public ?string $number = null;
    public string $subject = '';

    public ?int $activity_type_id = null;

    public ?string $letter_date = null;
    public ?string $start_date = null;
    public ?string $end_date = null;

    public ?string $location = null;
    public ?string $basis = null;
    public ?string $description = null;

    public array $personnel_ids = [];

    public function save(LetterService $service)
    {
        $this->authorize('create', \App\Models\Letter::class);

        $data = $this->validate([
            'number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'subject' => [
                'required',
                'string',
                'max:500',
            ],

            'activity_type_id' => [
                'required',
                'exists:activity_types,id',
            ],

            'letter_date' => [
                'required',
                'date',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'location' => [
                'required',
                'string',
                'max:500',
            ],

            'basis' => [
                'nullable',
                'string',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'personnel_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'personnel_ids.*' => [
                'integer',
                'distinct',
                'exists:personnels,id',
            ],
        ]);

        $letter = $service->createSpt($data);

        session()->flash(
            'success',
            'SPT berhasil dibuat.'
        );

        return $this->redirectRoute(
            'letters.show',
            $letter,
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.letters.create', [
            'activityTypes' => ActivityType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'personnels' => Personnel::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }
}