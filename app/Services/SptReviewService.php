<?php

namespace App\Services;

use App\Enums\LetterStatus;
use App\Models\Letter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SptReviewService
{
    public function verify(Letter $letter, int $actorId, ?string $note = null): Letter
    {
        return $this->transition($letter, $actorId, LetterStatus::Submitted, LetterStatus::Verified, 'verified', $note);
    }

    public function approve(Letter $letter, int $actorId, ?string $note = null): Letter
    {
        return $this->transition($letter, $actorId, LetterStatus::Verified, LetterStatus::Approved, 'approved', $note);
    }

    public function returnForRevision(Letter $letter, int $actorId, string $note): Letter
    {
        $note = trim($note);
        if (mb_strlen($note) < 10 || mb_strlen($note) > 2000) {
            throw ValidationException::withMessages(['reviewNote' => 'Alasan revisi harus berisi 10 sampai 2000 karakter.']);
        }

        return DB::transaction(function () use ($letter, $actorId, $note): Letter {
            $locked = Letter::query()->with('letterType')->lockForUpdate()->findOrFail($letter->id);
            $this->ensureReviewableSpt($locked);
            if (! in_array($locked->status, [LetterStatus::Submitted, LetterStatus::Verified], true)) {
                throw ValidationException::withMessages(['status' => 'Hanya pengajuan yang diajukan atau diverifikasi yang dapat dikembalikan.']);
            }
            $previous = $locked->status;
            $locked->forceFill(['status' => LetterStatus::Draft, 'updated_by' => $actorId])->save();
            $locked->submissionEvents()->create([
                'actor_id' => $actorId,
                'event' => 'returned_for_revision',
                'from_status' => $previous->value,
                'to_status' => LetterStatus::Draft->value,
                'note' => $note,
            ]);
            return $locked->fresh(['submissionEvents.actor']);
        }, 3);
    }

    private function transition(
        Letter $letter,
        int $actorId,
        LetterStatus $from,
        LetterStatus $to,
        string $event,
        ?string $note
    ): Letter {
        $note = trim((string) $note);
        if (mb_strlen($note) > 2000) {
            throw ValidationException::withMessages(['reviewNote' => 'Catatan maksimal 2000 karakter.']);
        }
        return DB::transaction(function () use ($letter, $actorId, $from, $to, $event, $note): Letter {
            $locked = Letter::query()->with('letterType')->lockForUpdate()->findOrFail($letter->id);
            $this->ensureReviewableSpt($locked);
            if ($locked->status !== $from) {
                throw ValidationException::withMessages(['status' => 'Status pengajuan sudah berubah. Muat ulang halaman dan periksa riwayatnya.']);
            }
            $changes = ['status' => $to, 'updated_by' => $actorId];
            if ($to === LetterStatus::Approved) {
                $changes['approved_by'] = $actorId;
                $changes['approved_at'] = now();
            }
            $locked->forceFill($changes)->save();
            $locked->submissionEvents()->create([
                'actor_id' => $actorId,
                'event' => $event,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'note' => $note !== '' ? $note : null,
            ]);
            return $locked->fresh(['submissionEvents.actor']);
        }, 3);
    }

    private function ensureReviewableSpt(Letter $letter): void
    {
        if ($letter->letterType?->code !== 'SPT' || $letter->source === 'import' || blank($letter->submission_reference)) {
            throw ValidationException::withMessages(['status' => 'Hanya pengajuan SPT baru yang telah diajukan yang dapat diproses.']);
        }
    }
}
