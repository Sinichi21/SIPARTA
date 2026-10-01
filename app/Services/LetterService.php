<?php

namespace App\Services;

use App\Enums\LetterRecordType;
use App\Enums\LetterStatus;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\PersonnelTeam;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LetterService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly LetterNumberService $numberService,
        private readonly LetterDocumentService $documents,
    ) {}

    public function createSpt(array $data, int $userId): Letter
    {
        return DB::transaction(function () use ($data, $userId) {
            $letterType = LetterType::query()
                ->where('code', 'SPT')
                ->where('is_active', true)
                ->firstOrFail();

            $personnelScope = $data['personnel_scope']
                ?? Letter::PERSONNEL_SCOPE_SELECTED;

            if (! in_array(
                $personnelScope,
                [
                    Letter::PERSONNEL_SCOPE_SELECTED,
                    Letter::PERSONNEL_SCOPE_TEAM,
                    Letter::PERSONNEL_SCOPE_ALL,
                ],
                true
            )) {
                throw ValidationException::withMessages([
                    'personnel_scope' => 'Cakupan personil tidak valid.',
                ]);
            }

            $personnelIds = [];
            $validPersonnelIds = [];
            $personnelTeamId = null;

            if ($personnelScope === Letter::PERSONNEL_SCOPE_TEAM) {
                $personnelTeamId = (int) ($data['personnel_team_id'] ?? 0);

                $team = PersonnelTeam::query()
                    ->where('is_active', true)
                    ->with(['personnels' => fn ($query) => $query->where('is_active', true)])
                    ->find($personnelTeamId);

                if (! $team) {
                    throw ValidationException::withMessages([
                        'personnel_team_id' => 'Tim personil tidak tersedia atau sudah nonaktif.',
                    ]);
                }

                $validPersonnelIds = $team->personnels
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($validPersonnelIds === []) {
                    throw ValidationException::withMessages([
                        'personnel_team_id' => 'Tim yang dipilih belum memiliki anggota aktif.',
                    ]);
                }
            }

            if (
                $personnelScope
                === Letter::PERSONNEL_SCOPE_SELECTED
            ) {
                $personnelIds = array_values(
                    array_unique(
                        array_map(
                            'intval',
                            $data['personnel_ids'] ?? []
                        )
                    )
                );

                $validPersonnelIds = Personnel::query()
                    ->whereIn('id', $personnelIds)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                sort($personnelIds);
                sort($validPersonnelIds);

                if ($personnelIds !== $validPersonnelIds) {
                    throw ValidationException::withMessages([
                        'personnel_ids' => 'Terdapat personil yang tidak tersedia atau sudah nonaktif.',
                    ]);
                }
            }

            $letter = Letter::create([
                'letter_type_id' => $letterType->id,
                'activity_type_id' => $data['activity_type_id'],

                'number' => filled($data['number'] ?? null)
                    ? trim($data['number'])
                    : null,

                'subject' => trim($data['subject']),
                'letter_date' => $data['letter_date'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],

                'location' => trim($data['location']),

                'basis' => filled($data['basis'] ?? null)
                    ? trim($data['basis'])
                    : null,

                'description' => filled($data['description'] ?? null)
                    ? trim($data['description'])
                    : null,

                'status' => LetterStatus::Draft,
                'record_type' => LetterRecordType::from($data['record_type'] ?? LetterRecordType::Normal->value),
                'personnel_scope' => $personnelScope,
                'personnel_team_id' => $personnelTeamId,
                'created_by' => $userId,
            ]);

            $letter->personnels()->sync($validPersonnelIds);

            $this->audit->created($letter);

            return $letter->fresh([
                'letterType',
                'activityType',
                'personnels',
                'creator',
            ]);
        });
    }

    public function updateSpt(
        Letter $letter,
        array $data,
        int $userId
    ): Letter {
        return DB::transaction(function () use (
            $letter,
            $data,
            $userId
        ) {
            $letter = $this->lockSptForUpdate($letter);

            if (! $letter->canBeEdited()) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya SPT draft atau hasil import yang dapat diubah.',
                ]);
            }

            $personnelScope = $data['personnel_scope']
                ?? $letter->personnel_scope
                ?? Letter::PERSONNEL_SCOPE_SELECTED;

            if (! in_array(
                $personnelScope,
                [
                    Letter::PERSONNEL_SCOPE_SELECTED,
                    Letter::PERSONNEL_SCOPE_TEAM,
                    Letter::PERSONNEL_SCOPE_ALL,
                ],
                true
            )) {
                throw ValidationException::withMessages([
                    'personnel_scope' => 'Cakupan personil tidak valid.',
                ]);
            }

            $personnelIds = [];
            $personnelTeamId = null;
            $previousPersonnelIds = $letter->personnels()
                ->pluck('personnels.id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $validPersonnelIds = [];

            if ($personnelScope === Letter::PERSONNEL_SCOPE_TEAM) {
                $personnelTeamId = (int) ($data['personnel_team_id'] ?? 0);

                $team = PersonnelTeam::query()
                    ->where('is_active', true)
                    ->with(['personnels' => fn ($query) => $query->where('is_active', true)])
                    ->find($personnelTeamId);

                if (! $team) {
                    throw ValidationException::withMessages([
                        'personnel_team_id' => 'Tim personil tidak tersedia atau sudah nonaktif.',
                    ]);
                }

                $validPersonnelIds = $team->personnels
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($validPersonnelIds === []) {
                    throw ValidationException::withMessages([
                        'personnel_team_id' => 'Tim yang dipilih belum memiliki anggota aktif.',
                    ]);
                }
            }

            if (
                $personnelScope
                === Letter::PERSONNEL_SCOPE_SELECTED
            ) {
                $personnelIds = array_values(
                    array_unique(
                        array_map(
                            'intval',
                            $data['personnel_ids'] ?? []
                        )
                    )
                );

                $validPersonnelIds = Personnel::query()
                    ->whereIn('id', $personnelIds)
                    ->where(function ($query) use ($letter, $previousPersonnelIds) {
                        $query->where('is_active', true);

                        if ($letter->source === 'import') {
                            $query->orWhereIn('id', $previousPersonnelIds);
                        }
                    })
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                sort($personnelIds);
                sort($validPersonnelIds);

                if ($personnelIds !== $validPersonnelIds) {
                    throw ValidationException::withMessages([
                        'personnel_ids' => 'Terdapat personil yang tidak tersedia atau sudah nonaktif.',
                    ]);
                }
            }

            $oldValues = $letter->getOriginal();

            $letter->update([
                'activity_type_id' => $data['activity_type_id'],

                'number' => filled($data['number'] ?? null)
                    ? trim($data['number'])
                    : null,

                'subject' => trim($data['subject']),
                'letter_date' => $data['letter_date'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],

                'location' => trim($data['location']),

                'basis' => filled($data['basis'] ?? null)
                    ? trim($data['basis'])
                    : null,

                'description' => filled($data['description'] ?? null)
                    ? trim($data['description'])
                    : null,

                'record_type' => LetterRecordType::from($data['record_type'] ?? LetterRecordType::Normal->value),
                'personnel_scope' => $personnelScope,
                'personnel_team_id' => $personnelTeamId,
                'updated_by' => $userId,
            ]);

            $letter->personnels()->sync($validPersonnelIds);

            $audit = $this->audit->updated(
                $letter,
                $oldValues
            );

            $audit->update([
                'old_values' => array_merge($audit->old_values ?? [], ['personnel_ids' => $previousPersonnelIds]),
                'new_values' => array_merge($audit->new_values ?? [], ['personnel_ids' => $validPersonnelIds, 'import_correction' => $letter->source === 'import']),
            ]);

            return $letter->fresh([
                'letterType',
                'activityType',
                'personnels',
                'creator',
                'updater',
            ]);
        });
    }

    public function publish(
        Letter $letter,
        int $userId
    ): Letter {
        return DB::transaction(function () use (
            $letter,
            $userId
        ) {
            $letter = $this->lockSptForUpdate($letter);

            if ($letter->status !== LetterStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya SPT berstatus draft yang dapat diterbitkan.',
                ]);
            }

            if (
                $letter->personnel_scope
                    !== Letter::PERSONNEL_SCOPE_ALL
                && $letter->personnels()->count() < 1
            ) {
                throw ValidationException::withMessages([
                    'personnel_ids' => 'SPT harus mempunyai minimal satu personil, menggunakan Tim, atau cakupan Seluruh Pegawai.',
                ]);
            }

            $oldValues = $letter->getOriginal();

            if (blank($letter->number)) {
                $letterType = $letter->letterType;

                $letter->number = $this->numberService->generate(
                    $letterType,
                    null,
                    $letter->letter_date?->year,
                    $letter->letter_date?->month,
                );
            }

            $letter->status = LetterStatus::Published;
            $letter->published_at = now();
            $letter->updated_by = $userId;

            $letter->save();

            $this->audit->published(
                $letter,
                $oldValues
            );
            $this->documents
                ->snapshotPublished(
                    $letter,
                    $userId
                );

            return $letter->fresh();
        });
    }

    private function lockSptForUpdate(Letter $letter): Letter
    {
        $locked = Letter::query()
            ->with('letterType')
            ->lockForUpdate()
            ->findOrFail($letter->id);

        if ($locked->letterType?->code !== 'SPT') {
            throw ValidationException::withMessages([
                'letter_type' => 'Operasi ini hanya berlaku untuk Surat Perintah Tugas (SPT).',
            ]);
        }

        return $locked;
    }

    public function cancel(
        Letter $letter,
        string $reason,
        int $userId
    ): Letter {
        return DB::transaction(function () use (
            $letter,
            $reason,
            $userId
        ) {
            $letter = $this->lockSptForUpdate($letter);

            $reason = trim($reason);

            if (
                mb_strlen($reason) < 5
                || mb_strlen($reason) > 1000
            ) {
                throw ValidationException::withMessages([
                    'cancellationReason' => 'Alasan pembatalan harus terdiri dari 5 sampai 1000 karakter.',
                ]);
            }

            if ($letter->status === LetterStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'status' => 'SPT sudah dibatalkan.',
                ]);
            }

            if (
                ! in_array(
                    $letter->status,
                    [
                        LetterStatus::Draft,
                        LetterStatus::Published,
                    ],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Status SPT ini tidak dapat dibatalkan.',
                ]);
            }

            $oldValues = $letter->getOriginal();

            $letter->update([
                'status' => LetterStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancellation_reason' => $reason,
                'updated_by' => $userId,
            ]);

            $this->audit->cancelled(
                $letter,
                $oldValues
            );

            return $letter->fresh();
        });
    }
}
