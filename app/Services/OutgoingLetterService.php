<?php

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Models\IssuedLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutgoingLetterService
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function verify(
        OutgoingLetter $letter,
        User $actor
    ): void {
        $this->transition(
            $letter,
            OutgoingLetterStatus::Draft,
            OutgoingLetterStatus::Verified,
            [
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]
        );
    }

    public function approve(
        OutgoingLetter $letter,
        User $actor
    ): void {
        $this->transition(
            $letter,
            OutgoingLetterStatus::Verified,
            OutgoingLetterStatus::Approved,
            [
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]
        );
    }

    public function number(
        OutgoingLetter $letter,
        User $actor,
        string $number,
        string $letterDate
    ): void {
        if ($letter->status !== OutgoingLetterStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => 'Surat harus disetujui sebelum diberi nomor.',
            ]);
        }

        $number = trim($number);

        validator(
            [
                'number' => $number,
                'letter_date' => $letterDate,
            ],
            [
                'number' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:outgoing_letters,number,'.$letter->id,
                ],
                'letter_date' => [
                    'required',
                    'date',
                ],
            ]
        )->validate();

        $old = $letter->getOriginal();

        $letter->forceFill([
            'number' => $number,
            'letter_date' => $letterDate,
            'status' => OutgoingLetterStatus::Numbered->value,
            'numbered_by' => $actor->id,
            'numbered_at' => now(),
            'updated_by' => $actor->id,
        ])->save();

        $this->audit->updated($letter, $old);
    }

    public function publish(
        OutgoingLetter $letter,
        User $actor
    ): IssuedLetter {
        if ($letter->status !== OutgoingLetterStatus::Numbered) {
            throw ValidationException::withMessages([
                'status' => 'Surat harus sudah bernomor sebelum diterbitkan.',
            ]);
        }

        if (! $letter->number || ! $letter->letter_date) {
            throw ValidationException::withMessages([
                'status' => 'Nomor dan tanggal surat wajib tersedia sebelum penerbitan.',
            ]);
        }

        return DB::transaction(function () use ($letter, $actor) {
            $old = $letter->getOriginal();

            $letter->forceFill([
                'status' => OutgoingLetterStatus::Published->value,
                'published_by' => $actor->id,
                'published_at' => now(),
                'updated_by' => $actor->id,
            ])->save();

            $profile = $letter->letterheadProfile;

            $issued = IssuedLetter::updateOrCreate(
                ['outgoing_letter_id' => $letter->id],
                [
                    'letter_type_id' => $letter->letter_type_id,
                    'number' => $letter->number,
                    'letter_date' => $letter->letter_date,
                    'subject' => $letter->subject,
                    'recipient' => $letter->recipient,
                    'signatory_name' => $profile?->signatory_name,
                    'signatory_nip' => $profile?->signatory_nip,
                    'signatory_position' => $profile?->signatory_position,
                    'issued_at' => $letter->published_at,
                    'issued_by' => $actor->id,
                    'status' => 'active',
                ]
            );

            $this->audit->published($letter, $old);
            $this->audit->created($issued);

            return $issued;
        });
    }

    public function send(
        OutgoingLetter $letter,
        User $actor
    ): void {
        $this->transition(
            $letter,
            OutgoingLetterStatus::Published,
            OutgoingLetterStatus::Sent,
            [
                'sent_by' => $actor->id,
                'sent_at' => now(),
            ]
        );
    }

    public function archive(
        OutgoingLetter $letter,
        User $actor
    ): void {
        $this->transition(
            $letter,
            OutgoingLetterStatus::Sent,
            OutgoingLetterStatus::Archived,
            [
                'archived_at' => now(),
            ]
        );
    }

    private function transition(
        OutgoingLetter $letter,
        OutgoingLetterStatus $from,
        OutgoingLetterStatus $to,
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
            [
                'status' => $to->value,
                'updated_by' => auth()->id(),
            ],
            $extra
        ))->save();

        $this->audit->updated($letter, $old);
    }
}
