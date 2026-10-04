<?php

namespace App\Services;

use App\Models\IssuedLetter;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

/** Editable, non-authoritative copy made ONLY from data frozen at issuance. */
class IssuedSptDocxExportService
{
    public function __construct(private readonly IssuedLetterVerificationService $verification) {}

    /** @return array{binary:string,filename:string} */
    public function export(IssuedLetter $issued): array
    {
        $issued->loadMissing('outgoingLetter.letterType');
        if ($issued->outgoingLetter?->letterType?->code !== 'SPT') {
            throw ValidationException::withMessages(['document' => 'Ekspor DOCX ini hanya untuk SPT resmi.']);
        }
        // Old documents do not have an immutable rendered-body snapshot. Do
        // not silently re-render mutable live data as if it were historical.
        $snapshot = $issued->snapshot_json ?? [];
        $body = $snapshot['docx_rendered_html'] ?? null;
        if (! is_string($body) || trim($body) === '') {
            throw ValidationException::withMessages([
                'document' => 'SPT ini diterbitkan sebelum snapshot DOCX tersedia. Gunakan PDF arsip resmi; DOCX historis tidak dibuat dari data yang mungkin sudah berubah.',
            ]);
        }
        $word = new PhpWord();
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(11);
        $word->getSettings()->setUpdateFields(false);
        $section = $word->addSection([
            'paperSize' => 'A4', 'marginTop' => 850, 'marginBottom' => 850,
            'marginLeft' => 950, 'marginRight' => 950,
        ]);
        $header = $snapshot['docx_letterhead'] ?? [];
        if (is_array($header)) {
            foreach (['parent_organization', 'directorate_name', 'organization_name'] as $field) {
                if (! empty($header[$field])) {
                    $section->addText((string) $header[$field], ['name' => 'Arial','size' => 11,'bold' => true,'color' => '4A4A4A'], ['spaceAfter' => 0]);
                }
            }
            $contact = array_filter([
                $header['address'] ?? null,
                !empty($header['phone']) ? 'Telp. '.$header['phone'] : null,
                $header['email'] ?? null,
            ]);
            if ($contact) $section->addText(implode(', ', $contact), ['size' => 8,'color' => '555555'], ['spaceAfter' => 100,'borderBottomSize' => 10,'borderBottomColor' => '555555']);
        }
        // Reject external/embedded HTML media (Word HTML importer can fetch URLs).
        $clean = preg_replace('~<(script|iframe|object|embed|img|svg|style)\b[^>]*>.*?</\1\s*>|<(img|embed)\b[^>]*\/?>~is', '', $body) ?? $body;
        Html::addHtml($section, $clean, false, false);

        $isCollective = ($snapshot['collective_annex'] ?? false) === true;
        if ($isCollective) {
            $people = $snapshot['personnel'] ?? [];
            if (! is_array($people) || $people === []) {
                throw ValidationException::withMessages(['document' => 'Snapshot personil kolektif tidak tersedia.']);
            }
            $section->addPageBreak();
            $section->addText('LAMPIRAN SURAT TUGAS KOLEKTIF', ['bold' => true,'size' => 12], ['alignment' => 'center']);
            $section->addText('Nomor: '.$issued->number, ['size' => 10], ['alignment' => 'center','spaceAfter' => 200]);
            $table = $section->addTable(['borderSize' => 5, 'borderColor' => '555555', 'cellMargin' => 65]);
            $table->addRow();
            foreach (['No', 'Nama', 'NIP', 'Pangkat / Gol.', 'Jabatan'] as $label) {
                $table->addCell()->addText($label, ['bold'=>true, 'size'=>9]);
            }
            foreach (array_values($people) as $i => $person) {
                $table->addRow();
                $rank = trim(implode(' / ', array_filter([$person['rank'] ?? null, $person['grade'] ?? null])));
                foreach ([(string)($i+1), $person['name'] ?? '', $person['nip'] ?? '', $rank, $person['position'] ?? ''] as $value) {
                    $table->addCell()->addText((string) $value, ['size'=>9]);
                }
            }
        }
        $section->addTextBreak(1);
        $section->addText('SALINAN DOCX YANG DAPAT DISUNTING — bukan arsip resmi atau bukti integritas.', ['size'=>8,'italic'=>true,'color'=>'666666']);
        $section->addText('Verifikasi surat dan lampiran: '.$this->verification->publicUrl($issued), ['size'=>8,'color'=>'555555']);
        $temp = tempnam(sys_get_temp_dir(), 'siparta-docx-');
        if ($temp === false) throw new \RuntimeException('Tidak dapat menyiapkan file DOCX sementara.');
        try {
            IOFactory::createWriter($word, 'Word2007')->save($temp);
            $binary = file_get_contents($temp);
            if ($binary === false) throw new \RuntimeException('Gagal membaca hasil DOCX.');
            return ['binary'=>$binary,'filename'=>'SPT-'.Str::slug($issued->number).'-editable.docx'];
        } finally {
            @unlink($temp);
        }
    }
}
