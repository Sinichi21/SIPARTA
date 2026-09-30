<?php

namespace App\Services;

use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterNumberSequence;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class OutgoingLetterNumberService
{
    public function next(
        CarbonInterface $date,
        ?LetterType $type
    ): string {
        return DB::transaction(function () use ($date, $type): string {
            $year = (int) $date->year;

            DB::table('outgoing_letter_number_sequences')
                ->insertOrIgnore([
                    'year' => $year,
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $sequence = OutgoingLetterNumberSequence::query()
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            do {
                $next = ((int) $sequence->last_number) + 1;
                $candidate = $this->render(
                    $type?->numbering_pattern
                        ?: '{sequence_padded}/{type}/{month_roman}/{year}',
                    [
                        'sequence' => (string) $next,
                        'sequence_padded' => str_pad((string) $next, 3, '0', STR_PAD_LEFT),
                        'type' => $type?->code ?: 'SURAT',
                        'month' => str_pad((string) $date->month, 2, '0', STR_PAD_LEFT),
                        'month_roman' => $this->romanMonth((int) $date->month),
                        'year' => (string) $date->year,
                        'year_short' => substr((string) $date->year, -2),
                    ]
                );

                $sequence->last_number = $next;
                $sequence->save();
            } while (
                OutgoingLetter::query()
                    ->where('number', $candidate)
                    ->exists()
            );

            return $candidate;
        }, 3);
    }

    private function render(
        string $format,
        array $tokens
    ): string {
        foreach ($tokens as $key => $value) {
            $format = str_replace(
                '{'.$key.'}',
                $value,
                $format
            );
        }

        return trim($format);
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
        ][$month] ?? (string) $month;
    }
}
