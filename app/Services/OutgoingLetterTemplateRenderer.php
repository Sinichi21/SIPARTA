<?php

namespace App\Services;

use App\Models\OutgoingLetter;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class OutgoingLetterTemplateRenderer
{
    /** @return array<int,string> */
    public function placeholders(string $html): array
    {
        preg_match_all('/{{\s*([A-Za-z0-9_-]+)\s*}}/', $html, $matches);

        return collect($matches[1] ?? [])->filter()->unique()->values()->all();
    }

    /** @return array<int,string> */
    public function manualPlaceholders(string $html): array
    {
        $system = array_keys($this->systemValues(new OutgoingLetter(), true));

        return collect($this->placeholders($html))
            ->reject(fn(string $name) => in_array($name, $system, true))
            ->values()
            ->all();
    }

    public function labelFor(string $placeholder): string
    {
        return Str::of($placeholder)->replace(['_','-'],' ')->squish()->title()->toString();
    }

    /** @return array<string,mixed> */
    public function values(OutgoingLetter $letter, bool $preview=false): array
    {
        $manual = is_array($letter->placeholder_data) ? $letter->placeholder_data : [];

        return array_merge($this->systemValues($letter, $preview), $manual);
    }

    public function render(OutgoingLetter $letter, bool $preview=false, bool $markMissing=false): HtmlString
    {
        $letter->loadMissing(['letterheadProfile','personnels','sourceSpt.activityType']);

        $html = $letter->content_html ?? '';
        $values = $this->values($letter, $preview);

        foreach ($this->placeholders($html) as $placeholder) {
            $value = $values[$placeholder] ?? null;

            if ($value instanceof HtmlString) {
                $replacement = (string) $value;
            } elseif ($value !== null && trim((string)$value) !== '') {
                $replacement = e((string)$value);
            } elseif ($markMissing) {
                $replacement = '<span style="color:#9a6700;background:#fff7d6;padding:0 2px;">['
                    .e($this->labelFor($placeholder)).' belum diisi]</span>';
            } else {
                $replacement = '';
            }

            $html = preg_replace(
                '/{{\s*'.preg_quote($placeholder,'/').'\s*}}/',
                $replacement,
                $html
            );
        }

        return new HtmlString($html);
    }

    /** @return array<int,string> */
    public function missingPlaceholders(OutgoingLetter $letter): array
    {
        $values=$this->values($letter,true);

        return collect($this->placeholders($letter->content_html ?? ''))
            ->filter(function(string $placeholder) use($values){
                $value=$values[$placeholder] ?? null;
                if ($value instanceof HtmlString) return trim((string)$value)==='';
                return $value===null || trim((string)$value)==='';
            })->values()->all();
    }

    /** @return array<string,mixed> */
    private function systemValues(OutgoingLetter $letter, bool $preview): array
    {
        $letter->loadMissing(['letterheadProfile','personnels','sourceSpt.activityType']);
        $head=$letter->letterheadProfile;
        $source=$letter->sourceSpt;

        $number=$letter->number;
        if ($preview && !$number) {
            $number=$letter->numbering_mode==='manual'
                ? ($letter->manual_number ?: '(nomor manual belum diisi)')
                : '(nomor otomatis saat terbit)';
        }

        $date=$letter->letter_date;
        if ($preview && !$date && $letter->date_mode==='manual') $date=$letter->manual_letter_date;
        $dateText=$date ? $date->translatedFormat('d F Y') : ($preview ? '(tanggal saat terbit)' : null);

        $period='-';
        if ($source?->start_date) {
            $period=$source->start_date->translatedFormat('d F Y');
            if ($source->end_date && !$source->end_date->equalTo($source->start_date)) {
                $period.=' s.d. '.$source->end_date->translatedFormat('d F Y');
            }
        }

        $personnelTable=$this->personnelTable($letter);
        $directPersonnel=app(SptPersonnelBlockRenderer::class)->render($letter->personnels);

        return [
            'nomor_surat'=>$number,'letter_number'=>$number,
            'tanggal_surat'=>$dateText,'letter_date'=>$dateText,
            'published_date'=>$letter->published_at?->translatedFormat('d F Y') ?: ($preview?'(belum diterbitkan)':null),
            'tujuan'=>$letter->recipient,'recipient'=>$letter->recipient,'penerima'=>$letter->recipient,
            'perihal'=>$letter->subject,'subject'=>$letter->subject,'klasifikasi'=>$letter->classification,
            'sifat'=>$letter->nature ? ucfirst($letter->nature) : null,
            'instansi'=>$head?->organization_name,'instansi_induk'=>$head?->parent_organization,
            'sub_instansi_induk'=>$head?->sub_parent_organization,'dirjen'=>$head?->sub_parent_organization,
            'alamat_instansi'=>$head?->address,'telepon_instansi'=>$head?->phone,'email_instansi'=>$head?->email,
            'website_instansi'=>$head?->website,'kota_surat'=>$head?->city,
            'nama_penandatangan'=>$head?->signatory_name,'nip_penandatangan'=>$head?->signatory_nip,
            'jabatan_penandatangan'=>$head?->signatory_position,

            // Sumber SPT / assignment
            'kegiatan'=>$source?->subject,
            'jenis_kegiatan'=>$source?->activityType?->name,
            'lokasi'=>$source?->location,
            'periode'=>$period,
            'tanggal_mulai'=>$source?->start_date?->translatedFormat('d F Y'),
            'tanggal_selesai'=>$source?->end_date?->translatedFormat('d F Y'),
            'dasar'=>$source?->basis,
            'keterangan'=>$source?->description,
            'jumlah_personil'=>(string)$letter->personnels->count(),
            'personil'=>$personnelTable,
            'personil_langsung'=>$directPersonnel,
            'personil_tabel'=>$personnelTable,
        ];
    }

    private function personnelTable(OutgoingLetter $letter): HtmlString
    {
        if ($letter->personnels->isEmpty()) return new HtmlString('');

        $rows=$letter->personnels->values()->map(function($person,$index){
            return '<tr>'
                .'<td style="border:1px solid #222;padding:5px;text-align:center;width:8%;">'.($index+1).'</td>'
                .'<td style="border:1px solid #222;padding:5px;width:37%;">'.e($person->name).'</td>'
                .'<td style="border:1px solid #222;padding:5px;width:25%;">'.e($person->nip ?: '-').'</td>'
                .'<td style="border:1px solid #222;padding:5px;width:30%;">'.e($person->position ?: '-').'</td>'
                .'</tr>';
        })->implode('');

        return new HtmlString(
            '<table style="width:100%;border-collapse:collapse;margin:6px 0 10px;font-size:11pt;">'
            .'<thead><tr>'
            .'<th style="border:1px solid #222;padding:5px;width:8%;">No</th>'
            .'<th style="border:1px solid #222;padding:5px;width:37%;">Nama</th>'
            .'<th style="border:1px solid #222;padding:5px;width:25%;">NIP</th>'
            .'<th style="border:1px solid #222;padding:5px;width:30%;">Jabatan</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table>'
        );
    }
}
