<?php

namespace App\Services;

use App\Models\ActivityType;
use Illuminate\Support\Str;

class SptActivityTypeMatcher
{
    /**
     * Mengembalikan ID jenis kegiatan berdasarkan teks historis.
     *
     * Strategi:
     * 1. exact match terhadap name/code master;
     * 2. keyword match konservatif;
     * 3. null bila tidak cukup yakin.
     *
     * Nilai yang tidak cocok TIDAK dipaksa menjadi "Kegiatan Lainnya"
     * agar kualitas data historis tetap terjaga.
     */
    public function matchId(?string $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        $normalized = $this->normalize($value);

        $exact = ActivityType::query()
            ->where(function ($query) use ($normalized) {
                $query
                    ->whereRaw('LOWER(name) = ?', [$normalized])
                    ->orWhereRaw('LOWER(code) = ?', [$normalized]);
            })
            ->value('id');

        if ($exact) {
            return (int) $exact;
        }

        $code = $this->matchCode($normalized);

        if (! $code) {
            return null;
        }

        return ActivityType::query()
            ->where('code', $code)
            ->value('id');
    }

    public function matchCode(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $text = $this->normalize($value);

        $rules = [
            'GANGGUAN' => [
                'gangguan',
                'interferensi',
                'interference',
            ],
            'PENERTIBAN' => [
                'penertiban',
                'penindakan',
            ],
            'PENGAWASAN' => [
                'pengawasan perangkat',
                'perangkat telekomunikasi',
                'sertifikasi perangkat',
                'sertifikasi alat',
                'apt',
            ],
            'PEMERIKSAAN' => [
                'pemeriksaan stasiun radio',
                'pemeriksaan radio',
                'inspeksi stasiun',
                'inspeksi radio',
            ],
            'PENGUKURAN' => [
                'pengukuran',
                'ukur ',
                'survey ukur',
                'survei ukur',
            ],
            'MONITORING' => [
                'monitoring',
                'monitor spektrum',
                'observasi spektrum',
                'pemantauan frekuensi',
                'pemantauan spektrum',
            ],
            'RAPAT' => [
                'rapat',
                'koordinasi',
                'meeting',
            ],
        ];

        foreach ($rules as $code => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($text, $keyword)) {
                    return $code;
                }
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = str_replace(
            ['_', '-', '/', '\\', '.', ',', ':', ';', '(', ')', '[', ']'],
            ' ',
            $value
        );

        return preg_replace('/\s+/', ' ', $value) ?: $value;
    }
}
