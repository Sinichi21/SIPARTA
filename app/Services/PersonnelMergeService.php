<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PersonnelMergeService
{
    public function __construct(
        private readonly AuditService $audit
    ) {
    }

    /**
     * Merge one or more duplicate personnel records into a primary record.
     *
     * The primary record is preserved. Letter relations and an optional user
     * relation are moved before duplicate records are soft deleted.
     *
     * @param  array<int, int|string>  $duplicateIds
     */
    public function merge(
        Personnel $primary,
        array $duplicateIds
    ): Personnel {
        $duplicateIds = collect($duplicateIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== $primary->id)
            ->unique()
            ->values()
            ->all();

        if ($duplicateIds === []) {
            throw ValidationException::withMessages([
                'merge' => 'Tidak ada record duplikat yang dipilih.',
            ]);
        }

        return DB::transaction(function () use ($primary, $duplicateIds) {
            $primary = Personnel::query()
                ->lockForUpdate()
                ->findOrFail($primary->id);

            $duplicates = Personnel::query()
                ->whereIn('id', $duplicateIds)
                ->lockForUpdate()
                ->get();

            if ($duplicates->count() !== count($duplicateIds)) {
                throw ValidationException::withMessages([
                    'merge' => 'Sebagian record duplikat tidak ditemukan atau sudah digabung sebelumnya.',
                ]);
            }

            $this->guardAgainstDifferentNips($primary, $duplicates->all());

            $primaryBefore = $primary->getOriginal();
            $movedLetters = 0;
            $mergedIds = [];

            foreach ($duplicates as $duplicate) {
                $movedLetters += $this->moveLetterRelations($primary, $duplicate);
                $this->moveUserRelation($primary, $duplicate);
                $this->fillMissingPrimaryData($primary, $duplicate);

                $mergedIds[] = $duplicate->id;
                $duplicate->delete();
            }

            $primary->save();
            $primary->refresh();

            $this->audit->merged(
                $primary,
                $primaryBefore,
                [
                    'merged_personnel_ids' => $mergedIds,
                    'moved_letter_relations' => $movedLetters,
                ]
            );

            return $primary;
        });
    }

    /**
     * Remove a clearly invalid imported personnel fragment from all SPTs and
     * soft delete it. This is intentionally separate from merge.
     */
    public function removeSuspicious(Personnel $personnel): void
    {
        DB::transaction(function () use ($personnel) {
            $personnel = Personnel::query()
                ->lockForUpdate()
                ->findOrFail($personnel->id);

            if (! $this->isSuspiciousName($personnel->name)) {
                throw ValidationException::withMessages([
                    'cleanup' => 'Record ini tidak memenuhi pola record mencurigakan.',
                ]);
            }

            $linkedUser = User::query()
                ->where('personnel_id', $personnel->id)
                ->exists();

            if ($linkedUser) {
                throw ValidationException::withMessages([
                    'cleanup' => 'Record terhubung dengan akun pengguna dan tidak dapat dibersihkan otomatis.',
                ]);
            }

            $oldValues = $personnel->getOriginal();
            $letterCount = $personnel->letters()->count();

            $personnel->letters()->detach();
            $personnel->delete();

            $this->audit->removedImportedPersonnel(
                $personnel,
                $oldValues,
                $letterCount
            );
        });
    }

    public function normalizeName(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));

        // Remove numbering prefixes that may have come from imported lists,
        // e.g. "1. I Made Sudana" or "2) I Made Sudana".
        $name = preg_replace(
            '/^\s*\d+\s*[\.\)]\s*/u',
            '',
            $name
        ) ?? $name;

        // Normalize punctuation first so variants such as:
        // "S.Kom", "S.Kom.", "S Kom", and "S.Kom," are
        // treated consistently.
        $name = str_replace([',', '.', ';', ':'], ' ', $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        $name = trim($name);

        // Remove common academic/professional titles from the END of the
        // name. This intentionally runs repeatedly because one person may
        // have multiple titles, for example "S.Kom., M.Kom.".
        $suffixPatterns = [
            's\s*kom', 'm\s*kom',
            's\s*t', 'm\s*t',
            's\s*si', 'm\s*si',
            's\s*e', 'm\s*m',
            's\s*h', 'm\s*h',
            's\s*sos', 'm\s*sos',
            's\s*ap', 'm\s*ap',
            's\s*ip', 'm\s*ip',
            's\s*pd', 'm\s*pd',
            's\s*psi', 'm\s*psi',
            's\s*tr',
            'a\s*md',
            'b\s*sc', 'm\s*sc', 'ph\s*d',
            'mba', 'cpa', 'cissp',
        ];

        $suffixRegex = '/\s+(?:' . implode('|', $suffixPatterns) . ')\s*$/u';

        do {
            $before = $name;
            $name = preg_replace($suffixRegex, '', $name) ?? $name;
            $name = trim($name);
        } while ($name !== $before);

        // Remove a limited set of honorific/professional prefixes when they
        // are written as a separate title. This helps match "Ir. Made X"
        // with "Made X" while avoiding arbitrary word removal.
        $name = preg_replace(
            '/^(?:prof|drs|dr|ir)\s+/u',
            '',
            $name
        ) ?? $name;

        // Final canonical form: letters/numbers and single spaces only.
        $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name) ?? $name;
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return trim($name);
    }

    public function isSuspiciousName(?string $name): bool
    {
        $name = trim((string) $name);

        if ($name === '') {
            return true;
        }

        // Records created by the old comma-based importer could contain only
        // degree/title fragments such as "S.T." or "M.T.".
        $compact = mb_strtolower(preg_replace('/\s+/u', '', $name) ?? $name);

        $knownFragments = [
            's.t.', 'st', 'm.t.', 'mt', 's.kom.', 'skom', 'm.kom.', 'mkom',
            's.si.', 'ssi', 'm.si.', 'msi', 's.e.', 'se', 'm.m.', 'mm',
            's.h.', 'sh', 'm.h.', 'mh', 's.sos.', 'ssos', 'm.sos.', 'msos',
            's.ap.', 'sap', 'm.ap.', 'map', 's.ip.', 'sip', 'm.ip.', 'mip',
        ];

        if (in_array($compact, $knownFragments, true)) {
            return true;
        }

        return mb_strlen($name) <= 2;
    }

    /**
     * @param array<int, Personnel> $duplicates
     */
    private function guardAgainstDifferentNips(
        Personnel $primary,
        array $duplicates
    ): void {
        $nips = collect([$primary, ...$duplicates])
            ->pluck('nip')
            ->filter(fn ($nip) => filled($nip))
            ->map(fn ($nip) => preg_replace('/\D+/u', '', (string) $nip))
            ->filter()
            ->unique()
            ->values();

        if ($nips->count() > 1) {
            throw ValidationException::withMessages([
                'merge' => 'Merge dibatalkan karena terdapat NIP berbeda. Periksa bahwa record benar-benar milik orang yang sama.',
            ]);
        }
    }

    private function moveLetterRelations(
        Personnel $primary,
        Personnel $duplicate
    ): int {
        $letterIds = $duplicate->letters()
            ->pluck('letters.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($letterIds !== []) {
            $primary->letters()->syncWithoutDetaching($letterIds);
            $duplicate->letters()->detach();
        }

        return count($letterIds);
    }

    private function moveUserRelation(
        Personnel $primary,
        Personnel $duplicate
    ): void {
        $duplicateUser = User::query()
            ->where('personnel_id', $duplicate->id)
            ->first();

        if (! $duplicateUser) {
            return;
        }

        $primaryUser = User::query()
            ->where('personnel_id', $primary->id)
            ->first();

        if ($primaryUser && $primaryUser->id !== $duplicateUser->id) {
            throw ValidationException::withMessages([
                'merge' => 'Merge dibatalkan karena record utama dan duplikat sama-sama terhubung ke akun pengguna berbeda.',
            ]);
        }

        $duplicateUser->forceFill([
            'personnel_id' => $primary->id,
        ])->save();
    }

    private function fillMissingPrimaryData(
        Personnel $primary,
        Personnel $duplicate
    ): void {
        foreach ([
            'unit_id',
            'nip',
            'rank',
            'grade',
            'position',
            'email',
            'phone',
        ] as $field) {
            if (blank($primary->{$field}) && filled($duplicate->{$field})) {
                $primary->{$field} = $duplicate->{$field};
            }
        }
    }
}
