<?php

namespace App\Services;

use App\Enums\LetterStatus;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SptSubmissionService
{
    public function __construct(private readonly AuditService $audit) {}

    public function submit(Letter $letter, int $actorId): Letter
    {
        $actor = User::query()->findOrFail($actorId);

        if (! $actor->can('letters.submit')) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk mengajukan SPT.');
        }

        return DB::transaction(function () use ($letter, $actorId): Letter {
            $locked = Letter::query()->with('letterType')->lockForUpdate()->findOrFail($letter->id);
            if ($locked->letterType?->code !== 'SPT' || $locked->source === 'import') {
                throw ValidationException::withMessages(['status' => 'Hanya draft SPT baru yang dapat diajukan.']);
            }
            if ($locked->status !== LetterStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Hanya draft SPT yang dapat diajukan.']);
            }
            if ($locked->personnel_scope !== Letter::PERSONNEL_SCOPE_ALL && ! $locked->personnels()->exists()) {
                throw ValidationException::withMessages(['status' => 'Pilih minimal satu personil sebelum mengajukan SPT.']);
            }
            if (blank($locked->activity_type_id) || blank($locked->subject) || blank($locked->location)
                || ! $locked->start_date || ! $locked->end_date || $locked->end_date->lt($locked->start_date)) {
                throw ValidationException::withMessages(['status' => 'Lengkapi informasi kegiatan, lokasi, dan periode penugasan.']);
            }
            // Reference is independent of official letter numbering and stable for this SPT ID.
            $reference = 'REQ-SPT-'.($locked->created_at?->format('Y') ?? now()->format('Y'))
                .'-'.str_pad((string) $locked->id, 6, '0', STR_PAD_LEFT);
            $locked->forceFill([
                'submission_reference' => $reference,
                'submitted_at' => now(),
                'submitted_by' => $actorId,
                'status' => LetterStatus::Submitted,
                'updated_by' => $actorId,
            ])->save();
            $locked->submissionEvents()->create([
                'actor_id' => $actorId,
                'event' => 'submitted',
                'from_status' => LetterStatus::Draft->value,
                'to_status' => LetterStatus::Submitted->value,
            ]);
            $this->audit->sptWorkflowEvent(
                $locked, 'SUBMIT', LetterStatus::Draft->value,
                LetterStatus::Submitted->value, $actorId
            );
            return $locked->fresh(['submissionEvents.actor']);
        }, 3);
    }
}
