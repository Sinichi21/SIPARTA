<?php

namespace App\Services;

use App\Enums\IncomingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class IncomingLetterService
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function dispose(
        IncomingLetter $letter,
        User $actor
    ): void {
        $this->transition(
            $letter,
            IncomingLetterStatus::Recorded,
            IncomingLetterStatus::Disposed,
            ['disposed_at' => now(), 'updated_by' => $actor->id]
        );
    }

    public function process(
        IncomingLetter $letter,
        User $actor
    ): void {
        $this->transition(
            $letter,
            IncomingLetterStatus::Disposed,
            IncomingLetterStatus::Processing,
            ['processed_at' => now(), 'updated_by' => $actor->id]
        );
    }

    public function complete(
        IncomingLetter $letter,
        User $actor
    ): void {
        $this->transition(
            $letter,
            IncomingLetterStatus::Processing,
            IncomingLetterStatus::Completed,
            ['completed_at' => now(), 'updated_by' => $actor->id]
        );
    }

    public function archive(
        IncomingLetter $letter,
        User $actor
    ): void {
        $this->transition(
            $letter,
            IncomingLetterStatus::Completed,
            IncomingLetterStatus::Archived,
            ['archived_at' => now(), 'updated_by' => $actor->id]
        );
    }

    private function transition(
        IncomingLetter $letter,
        IncomingLetterStatus $from,
        IncomingLetterStatus $to,
        array $extra
    ): void {
        if ($letter->status !== $from) {
            throw ValidationException::withMessages([
                'status' =>
                    "Status surat harus {$from->label()} sebelum menjadi {$to->label()}.",
            ]);
        }

        $old = $letter->getOriginal();

        $letter->forceFill(array_merge(
            ['status' => $to->value],
            $extra
        ))->save();

        $this->audit->updated($letter, $old);
    }
}
