<?php

namespace Database\Seeders;

use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Explicit opt-in installer: never updates pre-existing template bodies/default choices.
 */
class SptBuiltInTemplatesSeeder extends Seeder
{
    public const STANDARD = 'SPT_SYSTEM_STANDARD_V1';
    public const COLLECTIVE = 'SPT_SYSTEM_COLLECTIVE_V1';

    public function run(): void
    {
        $type = LetterType::query()->where('code', 'SPT')->first();
        if (! $type) {
            throw new RuntimeException('Jenis surat SPT belum tersedia. Jalankan LetterTypeSeeder terlebih dahulu.');
        }

        // Required by the existing non-nullable created_by FK. No phantom admin is created.
        $actorId = User::query()->orderBy('id')->value('id');
        if (! $actorId) {
            throw new RuntimeException('Buat akun admin terlebih dahulu sebelum menginstal template bawaan.');
        }

        DB::transaction(function () use ($type, $actorId): void {
            $hasDefault = LetterTemplate::query()->where('letter_type_id', $type->id)->where('is_default', true)->exists();
            foreach (self::definitions() as $key => $definition) {
                if (LetterTemplate::query()->where('code', $key)->exists()) {
                    $this->command?->info("Lewati {$key}: template sudah ada (tidak ditimpa).");
                    continue;
                }
                $makeDefault = ! $hasDefault && $key === self::STANDARD;
                LetterTemplate::query()->create([
                    'letter_type_id' => $type->id,
                    'letterhead_profile_id' => null,
                    'code' => $key,
                    'name' => $definition['name'],
                    'content_html' => $definition['content_html'],
                    'version' => 1,
                    'is_default' => $makeDefault,
                    'is_active' => true,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
                if ($makeDefault) $hasDefault = true;
            }
        });
    }

    /** @return array<string, array{name:string, content_html:string}> */
    public static function definitions(): array
    {
        // Kop is intentionally NOT embedded in the HTML body: x-official-letterhead
        // renders it exactly once for both HTML previews and DomPDF.
        $commonStart = '<p style="text-align:center;margin:0;"><strong><u>SURAT TUGAS</u></strong><br>Nomor: {{nomor_surat}}</p>'
            .'<p><strong>Menimbang</strong> : {{pertimbangan}}</p>'
            .'<p><strong>Dasar</strong> : {{dasar}}</p>'
            .'<p style="text-align:center;"><strong>MEMBERI TUGAS :</strong></p>';
        $commonEnd = '<p><strong>Untuk</strong> : {{kegiatan}}<br>Tempat: {{lokasi}}<br>Waktu: {{periode}}</p>'
            .'<p>Demikian surat tugas ini dibuat untuk dilaksanakan dengan sebaik-baiknya dan penuh tanggung jawab.</p>'
            .'<p style="text-align:right;">{{kota_surat}}, {{tanggal_surat}}<br>{{jabatan_penandatangan}}<br><br><br><strong>{{nama_penandatangan}}</strong><br>{{nip_penandatangan}}</p>';
        return [
            self::STANDARD => [
                'name' => 'SPT Bawaan - Personil Langsung',
                'content_html' => $commonStart.'<p><strong>Kepada</strong> :</p>{{personil_langsung}}'.$commonEnd,
            ],
            self::COLLECTIVE => [
                'name' => 'SPT Bawaan - Kolektif (Konsep)',
                'content_html' => $commonStart.'<p><strong>Kepada</strong> : Daftar personil terlampir.</p>'.$commonEnd
                    .'<p><em>Catatan: lampiran pada konsep ini belum merupakan arsip resmi terpisah. Jangan gunakan untuk penerbitan kolektif sebelum modul lampiran terverifikasi tersedia.</em></p>',
            ],
        ];
    }
}
