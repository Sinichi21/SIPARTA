<?php

namespace App\Services;

use App\Enums\LetterRecordType;
use App\Enums\LetterStatus;
use App\Models\ActivityType;
use App\Models\ImportBatch;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SptImportService
{
    public function import(
        array $rows,
        array $mapping,
        string $filename,
        int $userId
    ): ImportBatch {
        return DB::transaction(function () use ($rows, $mapping, $filename, $userId) {
            $batch = ImportBatch::create([
                'original_filename' => $filename,
                'status' => 'processing',
                'total_rows' => count($rows),
                'mapping' => $mapping,
                'uploaded_by' => $userId,
            ]);

            $letterType = LetterType::query()
                ->where('code', 'SPT')
                ->firstOrFail();

            $imported = 0;
            $failed = 0;
            $duplicates = 0;
            $allPersonnelScope = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    $number = $this->mapped($row, $mapping, 'number');
                    $letterDate = $this->parseDate($this->mapped($row, $mapping, 'letter_date'));
                    $startDate = $this->parseDate($this->mapped($row, $mapping, 'start_date'));
                    $endDate = $this->parseDate($this->mapped($row, $mapping, 'end_date'));
                    $activityText = $this->mapped($row, $mapping, 'activity');
                    $subject = $this->mapped($row, $mapping, 'subject') ?: $activityText ?: 'SPT Lama';
                    $recordType = $this->normalizeRecordType($this->mapped($row, $mapping, 'record_type'));

                    if (blank($number)) {
                        throw new \RuntimeException('Nomor SPT kosong.');
                    }

                    if (Letter::query()
                        ->where('letter_type_id', $letterType->id)
                        ->where('number', trim($number))
                        ->exists()) {
                        $duplicates++;
                        continue;
                    }

                    $activityTypeId = null;

                    if (filled($activityText)) {
                        $activityTypeId = ActivityType::query()
                            ->whereRaw('LOWER(name) = ?', [Str::lower(trim($activityText))])
                            ->value('id');
                    }

                    $personnelNamesRaw = $this->mapped(
                        $row,
                        $mapping,
                        'personnel_names'
                    );
                    $personnelNipsRaw = $this->mapped(
                        $row,
                        $mapping,
                        'personnel_nips'
                    );

                    $assignsAllPersonnel = self::isAllPersonnelValue(
                        $personnelNamesRaw
                    );

                    $letter = Letter::create([
                        'letter_type_id' => $letterType->id,
                        'activity_type_id' => $activityTypeId,
                        'number' => trim($number),
                        'subject' => trim($subject),
                        'letter_date' => $letterDate,
                        'start_date' => $startDate ?: $letterDate,
                        'end_date' => $endDate ?: $startDate ?: $letterDate,
                        'location' => $this->nullableTrim($this->mapped($row, $mapping, 'location')),
                        'description' => $this->nullableTrim($this->mapped($row, $mapping, 'description')),
                        'status' => LetterStatus::Published,
                        'source' => 'import',
                        'record_type' => $recordType,
                        'personnel_scope' => $assignsAllPersonnel
                            ? Letter::PERSONNEL_SCOPE_ALL
                            : Letter::PERSONNEL_SCOPE_SELECTED,
                        'import_batch_id' => $batch->id,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                        'published_at' => now(),
                    ]);

                    if ($assignsAllPersonnel) {
                        $allPersonnelScope++;
                    } else {
                        $personnelIds = $this->resolvePersonnels(
                            $personnelNamesRaw,
                            $personnelNipsRaw
                        );

                        if ($personnelIds !== []) {
                            $letter->personnels()->sync($personnelIds);
                        }
                    }

                    $imported++;
                } catch (Throwable $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $index + 2,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            $batch->update([
                'status' => $failed > 0 ? 'completed_with_errors' : 'completed',
                'valid_rows' => $imported + $duplicates,
                'failed_rows' => $failed,
                'imported_rows' => $imported,
                'summary' => [
                    'duplicates' => $duplicates,
                    'all_personnel_scope' => $allPersonnelScope,
                    'errors' => array_slice($errors, 0, 50),
                ],
                'completed_at' => now(),
            ]);

            return $batch->fresh();
        });
    }

    private function resolvePersonnels(?string $namesRaw, ?string $nipsRaw): array
    {
        $names = $this->splitMulti($namesRaw);
        $nips = $this->splitMulti($nipsRaw);
        $count = max(count($names), count($nips));
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $name = trim($names[$i] ?? '');
            $nip = trim($nips[$i] ?? '');

            if ($name === '' && $nip === '') {
                continue;
            }

            $personnel = null;

            if ($nip !== '') {
                $personnel = Personnel::query()->where('nip', $nip)->first();
            }

            if (! $personnel && $name !== '') {
                $personnel = Personnel::query()
                    ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                    ->first();
            }

            if (! $personnel) {
                $personnel = Personnel::create([
                    'nip' => $nip !== '' ? $nip : null,
                    'name' => $name !== '' ? $name : 'Personil Import',
                    'is_active' => true,
                ]);
            }

            $ids[] = $personnel->id;
        }

        return array_values(array_unique($ids));
    }

    private function splitMulti(?string $value): array
    {
        if (blank($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[;\n|]+/', $value) ?: []
        )));
    }

    public static function isAllPersonnelValue(?string $value): bool
    {
        if (blank($value)) {
            return false;
        }

        $aliases = [
            'all pegawai',
            'all personil',
            'semua pegawai',
            'semua personil',
            'seluruh pegawai',
            'seluruh personil',
        ];

        $parts = preg_split('/[;\n|]+/', $value) ?: [];

        foreach ($parts as $part) {
            $normalized = Str::lower(trim($part));
            $normalized = str_replace(
                ['.', ',', '-', '_'],
                ' ',
                $normalized
            );
            $normalized = preg_replace('/\s+/', ' ', $normalized) ?: $normalized;

            if (in_array($normalized, $aliases, true)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeRecordType(?string $value): LetterRecordType
    {
        if (blank($value)) {
            return LetterRecordType::Normal;
        }

        $normalized = Str::lower(trim($value));
        $normalized = str_replace(['-', '_'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?: $normalized;

        return match ($normalized) {
            'attendance correction',
            'koreksi absensi',
            'lupa absen',
            'fiktif',
            'spt fiktif',
            'administratif',
            'administrasi' => LetterRecordType::AttendanceCorrection,
            default => LetterRecordType::Normal,
        };
    }

    private function mapped(array $row, array $mapping, string $key): ?string
    {
        $column = $mapping[$key] ?? null;

        if (blank($column)) {
            return null;
        }

        $value = $row[$column] ?? null;

        return is_scalar($value) ? trim((string) $value) : null;
    }

    private function nullableTrim(?string $value): ?string
    {
        return filled($value) ? trim($value) : null;
    }

    private function parseDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = trim($value);

        if (is_numeric($value)) {
            $serial = (float) $value;

            if ($serial > 20000 && $serial < 100000) {
                return Carbon::create(1899, 12, 30)
                    ->addDays((int) floor($serial))
                    ->toDateString();
            }
        }

        foreach ([
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'd.m.Y',
            'j/n/Y',
            'j-n-Y',
        ] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date !== false) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            throw new \RuntimeException("Format tanggal '{$value}' tidak dikenali.");
        }
    }
}
