<?php

namespace App\Services;

use App\Models\LetterNumberSequence;
use App\Models\LetterType;
use Illuminate\Support\Facades\DB;

class LetterNumberService
{
    public function generate(
        LetterType $letterType,
        ?int $unitId = null,
        ?int $year = null,
        ?int $month = null
    ): string {
        $year ??= now()->year;
        $month ??= now()->month;

        return DB::transaction(function () use (
            $letterType,
            $unitId,
            $year,
            $month
        ) {
            $sequence = LetterNumberSequence::query()
                ->where('letter_type_id', $letterType->id)
                ->where('unit_id', $unitId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = LetterNumberSequence::create([
                    'letter_type_id' => $letterType->id,
                    'unit_id' => $unitId,
                    'year' => $year,
                    'last_number' => 0,
                ]);
            }

            $sequence->increment('last_number');

            $sequence->refresh();

            $pattern = $letterType->numbering_pattern
                ?: '{sequence}/{type}/{month_roman}/{year}';

            return strtr($pattern, [
                '{sequence}' => str_pad(
                    (string) $sequence->last_number,
                    3,
                    '0',
                    STR_PAD_LEFT
                ),

                '{type}' => $letterType->code,

                '{year}' => (string) $year,

                '{month}' => str_pad(
                    (string) $month,
                    2,
                    '0',
                    STR_PAD_LEFT
                ),

                '{month_roman}' => $this->romanMonth($month),

                '{unit}' => $unitId
                    ? (string) $unitId
                    : '',
            ]);
        });
    }

    private function romanMonth(int $month): string
    {
        return [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ][$month];
    }
}