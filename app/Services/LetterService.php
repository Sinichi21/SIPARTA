<?php

namespace App\Services;

use App\Enums\LetterRecordType;
use App\Enums\LetterStatus;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LetterService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly LetterNumberService $numberService,
    ) {
    }

    public function createSpt(array $data, int $userId): Letter
    {
        return DB::transaction(function () use ($data, $userId) {
            $letterType = LetterType::query()
                ->where('code', 'SPT')
                ->where('is_active', true)
                ->firstOrFail();

            $personnelIds = array_values(
                array_unique(
                    array_map('intval', $data['personnel_ids'])
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
                    'personnel_ids' =>
                        'Terdapat personil yang tidak tersedia atau sudah nonaktif.',
                ]);
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
            if ($letter->status !== LetterStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Hanya SPT berstatus draft yang dapat diubah.',
                ]);
            }

            $personnelIds = array_values(
                array_unique(
                    array_map('intval', $data['personnel_ids'])
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
                    'personnel_ids' =>
                        'Terdapat personil yang tidak tersedia atau sudah nonaktif.',
                ]);
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
                'updated_by' => $userId,
            ]);

            $letter->personnels()->sync($validPersonnelIds);

            $this->audit->updated(
                $letter,
                $oldValues
            );

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
            if ($letter->status !== LetterStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Hanya SPT berstatus draft yang dapat diterbitkan.',
                ]);
            }

            if ($letter->personnels()->count() < 1) {
                throw ValidationException::withMessages([
                    'personnel_ids' =>
                        'SPT harus mempunyai minimal satu personil.',
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

            return $letter->fresh();
        });
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
            if ($letter->status === LetterStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'status' =>
                        'SPT sudah dibatalkan.',
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
                    'status' =>
                        'Status SPT ini tidak dapat dibatalkan.',
                ]);
            }

            $oldValues = $letter->getOriginal();

            $letter->update([
                'status' => LetterStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancellation_reason' => trim($reason),
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