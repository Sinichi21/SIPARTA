<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\LetterTemplate;
use Illuminate\Support\HtmlString;

class LetterTemplateRenderer
{
    public function placeholders(Letter $letter): array
    {
        $personnel = $letter->assignsAllPersonnel()
            ? 'Seluruh Pegawai'
            : $letter->personnels
                ->pluck('name')
                ->filter()
                ->implode(', ');

        $units = $letter->assignsAllPersonnel()
            ? '-'
            : ($letter->personnels
                ->pluck('unit.name')
                ->filter()
                ->unique()
                ->implode(', ') ?: '-');

        $period = '-';

        if ($letter->start_date) {
            $period = $letter->start_date->translatedFormat('d F Y');

            if (
                $letter->end_date
                && ! $letter->end_date->equalTo($letter->start_date)
            ) {
                $period .= ' s.d. '
                    .$letter->end_date->translatedFormat('d F Y');
            }
        }

        return [
            'nomor_surat' => $letter->number ?: 'Belum bernomor',
            'tanggal_surat' => $letter->letter_date?->translatedFormat('d F Y') ?: '-',
            'kegiatan' => $letter->subject ?: $letter->activityType?->name ?: '-',
            'jenis_kegiatan' => $letter->activityType?->name ?: 'Belum dikategorikan',
            'lokasi' => $letter->location ?: '-',
            'periode' => $period,
            'tanggal_mulai' => $letter->start_date?->translatedFormat('d F Y') ?: '-',
            'tanggal_selesai' => $letter->end_date?->translatedFormat('d F Y') ?: '-',
            'personil' => $personnel ?: '-',
            'jumlah_personil' => $letter->assignsAllPersonnel()
                ? 'Seluruh Pegawai'
                : (string) $letter->personnels->count(),
            'unit_tim' => $units,
            'dasar' => $letter->basis ?: '-',
            'keterangan' => $letter->description ?: '-',
        ];
    }

    public function render(
        LetterTemplate $template,
        Letter $letter
    ): HtmlString {
        $html = $template->content_html ?? '';

        foreach ($this->placeholders($letter) as $key => $value) {
            $html = str_replace('{{ '.$key.' }}', e($value), $html);
            $html = str_replace('{{'.$key.'}}', e($value), $html);
        }

        return new HtmlString($html);
    }
}
