<?php

namespace App\Services;

use App\Models\OutgoingLetter;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class OutgoingLetterTemplateRenderer
{
    /** @return array<int, string> */
    public function placeholders(string $html): array
    {
        preg_match_all(
            '/{{\s*([A-Za-z0-9_-]+)\s*}}/',
            $html,
            $matches
        );

        return collect($matches[1] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    public function manualPlaceholders(string $html): array
    {
        $system = array_keys(
            $this->systemValues(new OutgoingLetter(), true)
        );

        return collect($this->placeholders($html))
            ->reject(fn (string $name) => in_array($name, $system, true))
            ->values()
            ->all();
    }

    public function labelFor(string $placeholder): string
    {
        return Str::of($placeholder)
            ->replace(['_', '-'], ' ')
            ->squish()
            ->title()
            ->toString();
    }

    /** @return array<string, string|null> */
    public function values(
        OutgoingLetter $letter,
        bool $preview = false
    ): array {
        $manual = is_array($letter->placeholder_data)
            ? $letter->placeholder_data
            : [];

        return array_merge(
            $this->systemValues($letter, $preview),
            $manual
        );
    }

    public function render(
        OutgoingLetter $letter,
        bool $preview = false,
        bool $markMissing = false
    ): HtmlString {
        $html = $letter->content_html ?? '';
        $values = $this->values($letter, $preview);

        foreach ($this->placeholders($html) as $placeholder) {
            $value = $values[$placeholder] ?? null;

            if ($value !== null && trim((string) $value) !== '') {
                $html = preg_replace(
                    '/{{\s*'.preg_quote($placeholder, '/').'\s*}}/',
                    e((string) $value),
                    $html
                );

                continue;
            }

            if ($markMissing) {
                $html = preg_replace(
                    '/{{\s*'.preg_quote($placeholder, '/').'\s*}}/',
                    '<span style="color:#9a6700;background:#fff7d6;padding:0 2px;">['
                    .e($this->labelFor($placeholder))
                    .' belum diisi]</span>',
                    $html
                );
            }
        }

        return new HtmlString($html);
    }

    /** @return array<int, string> */
    public function missingPlaceholders(
        OutgoingLetter $letter
    ): array {
        $values = $this->values($letter, true);

        return collect(
            $this->placeholders(
                $letter->content_html ?? ''
            )
        )
            ->filter(function (string $placeholder) use ($values) {
                $value = $values[$placeholder] ?? null;

                return $value === null
                    || trim((string) $value) === '';
            })
            ->values()
            ->all();
    }

    /** @return array<string, string|null> */
    private function systemValues(
        OutgoingLetter $letter,
        bool $preview
    ): array {
        $letterhead = $letter->letterheadProfile;

        $number = $letter->number;

        if (
            $preview
            && ! $number
        ) {
            $number = $letter->numbering_mode === 'manual'
                ? ($letter->manual_number ?: '(nomor manual belum diisi)')
                : '(nomor otomatis saat terbit)';
        }

        $date = $letter->letter_date;

        if (
            $preview
            && ! $date
        ) {
            $date = $letter->date_mode === 'manual'
                ? $letter->manual_letter_date
                : null;
        }

        $dateText = $date
            ? $date->translatedFormat('d F Y')
            : ($preview ? '(tanggal saat terbit)' : null);

        return [
            'nomor_surat' => $number,
            'letter_number' => $number,
            'tanggal_surat' => $dateText,
            'letter_date' => $dateText,
            'published_date' => $letter->published_at
                ?->translatedFormat('d F Y')
                ?: ($preview ? '(belum diterbitkan)' : null),
            'tujuan' => $letter->recipient,
            'recipient' => $letter->recipient,
            'penerima' => $letter->recipient,
            'perihal' => $letter->subject,
            'subject' => $letter->subject,
            'klasifikasi' => $letter->classification,
            'sifat' => $letter->nature
                ? ucfirst($letter->nature)
                : null,
            'instansi' => $letterhead?->organization_name,
            'instansi_induk' => $letterhead?->parent_organization,
            'alamat_instansi' => $letterhead?->address,
            'telepon_instansi' => $letterhead?->phone,
            'email_instansi' => $letterhead?->email,
            'website_instansi' => $letterhead?->website,
            'kota_surat' => $letterhead?->city,
            'nama_penandatangan' => $letterhead?->signatory_name,
            'nip_penandatangan' => $letterhead?->signatory_nip,
            'jabatan_penandatangan' => $letterhead?->signatory_position,
        ];
    }
}
