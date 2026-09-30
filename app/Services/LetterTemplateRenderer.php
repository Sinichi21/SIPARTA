<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\LetterheadProfile;
use Illuminate\Support\HtmlString;

class LetterTemplateRenderer
{
    public function resolveLetterhead(
        LetterTemplate $template
    ): ?LetterheadProfile {
        if ($template->letterhead_profile_id) {
            return LetterheadProfile::query()
                ->whereKey(
                    $template->letterhead_profile_id
                )
                ->where('is_active', true)
                ->first();
        }

        return LetterheadProfile::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    public function placeholders(
        Letter $letter,
        ?LetterheadProfile $letterhead = null
    ): array {
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
            $period = $letter->start_date
                ->translatedFormat('d F Y');

            if (
                $letter->end_date
                && ! $letter->end_date
                    ->equalTo($letter->start_date)
            ) {
                $period .= ' s.d. '
                    .$letter->end_date
                        ->translatedFormat('d F Y');
            }
        }

        return [
            'nomor_surat' => $letter->number
                ?: 'Belum bernomor',
            'tanggal_surat' => $letter->letter_date
                ?->translatedFormat('d F Y') ?: '-',
            'kegiatan' => $letter->subject
                ?: $letter->activityType?->name
                ?: '-',
            'jenis_kegiatan' =>
                $letter->activityType?->name
                ?: 'Belum dikategorikan',
            'lokasi' => $letter->location ?: '-',
            'periode' => $period,
            'tanggal_mulai' => $letter->start_date
                ?->translatedFormat('d F Y') ?: '-',
            'tanggal_selesai' => $letter->end_date
                ?->translatedFormat('d F Y') ?: '-',
            'personil' => $personnel ?: '-',
            'jumlah_personil' =>
                $letter->assignsAllPersonnel()
                    ? 'Seluruh Pegawai'
                    : (string)
                        $letter->personnels->count(),
            'unit_tim' => $units,
            'dasar' => $letter->basis ?: '-',
            'keterangan' => $letter->description
                ?: '-',

            'instansi' =>
                $letterhead?->organization_name ?: '-',
            'instansi_induk' =>
                $letterhead?->parent_organization
                ?: '-',
            'alamat_instansi' =>
                $letterhead?->address ?: '-',
            'telepon_instansi' =>
                $letterhead?->phone ?: '-',
            'email_instansi' =>
                $letterhead?->email ?: '-',
            'website_instansi' =>
                $letterhead?->website ?: '-',
            'kota_surat' =>
                $letterhead?->city ?: '-',
            'nama_penandatangan' =>
                $letterhead?->signatory_name ?: '-',
            'nip_penandatangan' =>
                $letterhead?->signatory_nip ?: '-',
            'jabatan_penandatangan' =>
                $letterhead?->signatory_position
                ?: '-',
        ];
    }

    public function render(
        LetterTemplate $template,
        Letter $letter
    ): HtmlString {
        $html = $template->content_html ?? '';

        $letterhead =
            $this->resolveLetterhead($template);

        foreach (
            $this->placeholders(
                $letter,
                $letterhead
            )
            as $key => $value
        ) {
            $html = str_replace(
                '{{ '.$key.' }}',
                e($value),
                $html
            );

            $html = str_replace(
                '{{'.$key.'}}',
                e($value),
                $html
            );
        }

        return new HtmlString($html);
    }
}
