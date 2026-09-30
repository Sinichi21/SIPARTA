<?php

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Models\IssuedLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutgoingLetterService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly OutgoingLetterNumberService $numbers,
        private readonly IssuedLetterArchiveService $archive,
    ) {}

    public function verify(OutgoingLetter $letter, User $actor): void
    {
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

    public function approve(OutgoingLetter $letter, User $actor): void
    {
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

    /**
     * Backward-compatible entry point for Phase 13 tests / older UI.
     *
     * Important: this does NOT allocate a final number yet.
     * It only stores a manual candidate and moves the legacy workflow
     * to Numbered. The final number/date are committed by publish().
     */
    public function number(
        OutgoingLetter $letter,
        User $actor,
        string $number,
        string $letterDate
    ): void {
        if ($letter->status !== OutgoingLetterStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => 'Surat harus disetujui sebelum diberi kandidat nomor.',
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
                ],
                'letter_date' => [
                    'required',
                    'date',
                ],
            ]
        )->validate();

        $used = OutgoingLetter::query()
            ->where('number', $number)
            ->whereKeyNot($letter->id)
            ->exists();

        if ($used) {
            throw ValidationException::withMessages([
                'number' => 'Nomor surat sudah digunakan oleh surat lain.',
            ]);
        }

        $old = $letter->getOriginal();

        $letter->forceFill([
            'numbering_mode' => 'manual',
            'manual_number' => $number,
            'date_mode' => 'manual',
            'manual_letter_date' => $letterDate,

            // Final number/date intentionally remain null until publish().
            'number' => null,
            'letter_date' => null,

            // Keep legacy state compatibility only.
            'status' => OutgoingLetterStatus::Numbered->value,
            'numbered_by' => $actor->id,
            'numbered_at' => now(),
            'updated_by' => $actor->id,
        ])->save();

        $this->audit->updated($letter, $old);
    }

    public function publish(OutgoingLetter $letter, User $actor): IssuedLetter
    {
        if (! in_array(
            $letter->status,
            [OutgoingLetterStatus::Approved, OutgoingLetterStatus::Numbered],
            true
        )) {
            throw ValidationException::withMessages([
                'status' => 'Surat harus disetujui sebelum diterbitkan.',
            ]);
        }

        $issued = DB::transaction(function () use ($letter, $actor): IssuedLetter {
            $locked = OutgoingLetter::query()
                ->whereKey($letter->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === OutgoingLetterStatus::Published) {
                return $locked->issuedLetter()->firstOrFail();
            }

            $finalDate = $locked->date_mode === 'manual'
                ? $locked->manual_letter_date
                : now()->toDateString();

            if (! $finalDate) {
                throw ValidationException::withMessages([
                    'manual_letter_date' => 'Tanggal manual wajib diisi.',
                ]);
            }

            if ($locked->numbering_mode === 'manual') {
                $finalNumber = trim((string) $locked->manual_number);

                if ($finalNumber === '') {
                    throw ValidationException::withMessages([
                        'manual_number' => 'Nomor surat manual wajib diisi.',
                    ]);
                }

                $used = OutgoingLetter::query()
                    ->where('number', $finalNumber)
                    ->whereKeyNot($locked->id)
                    ->exists();

                if ($used) {
                    throw ValidationException::withMessages([
                        'manual_number' => 'Nomor surat manual sudah digunakan oleh surat lain.',
                    ]);
                }
            } else {
                $locked->loadMissing('letterType');

                $finalNumber = $this->numbers->next(
                    Carbon::parse($finalDate),
                    $locked->letterType
                );
            }

            $old = $locked->getOriginal();

            $locked->forceFill([
                'number' => $finalNumber,
                'letter_date' => $finalDate,
                'status' => OutgoingLetterStatus::Published->value,
                'numbered_by' => $actor->id,
                'numbered_at' => now(),
                'published_by' => $actor->id,
                'published_at' => now(),
                'updated_by' => $actor->id,
            ])->save();

            $locked->loadMissing('letterheadProfile');

            $profile = $locked->letterheadProfile;

            $issued = IssuedLetter::updateOrCreate(
                ['outgoing_letter_id' => $locked->id],
                [
                    'letter_type_id' => $locked->letter_type_id,
                    'number' => $locked->number,
                    'letter_date' => $locked->letter_date,
                    'subject' => $locked->subject,
                    'recipient' => $locked->recipient,
                    'signatory_name' => $profile?->signatory_name,
                    'signatory_nip' => $profile?->signatory_nip,
                    'signatory_position' => $profile?->signatory_position,
                    'issued_at' => $locked->published_at,
                    'issued_by' => $actor->id,
                    'status' => 'active',
                ]
            );

            $this->audit->published($locked, $old);
            $this->audit->created($issued);

            $letter->setRawAttributes($locked->getAttributes(), true);

            return $issued;
        }, 3);

        if (! $issued->hasArchivedPdf()) {
            $issued = $this->archive->archive(
                $issued,
                $letter->fresh()
            );
        }

        return $issued;
    }

    public function send(OutgoingLetter $letter, User $actor): void
    {
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

    public function archive(OutgoingLetter $letter, User $actor): void
    {
        $this->transition(
            $letter,
            OutgoingLetterStatus::Sent,
            OutgoingLetterStatus::Archived,
            ['archived_at' => now()]
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
                'status' => "Status surat harus {$from->label()} sebelum menjadi {$to->label()}.",
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
