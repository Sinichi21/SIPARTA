<?php

namespace Database\Seeders;

use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\User;
use App\Services\SptCollectiveDocumentService;
use Illuminate\Database\Seeder;
use RuntimeException;

/** Opt-in; does not overwrite existing templates or default selection. */
class SptCollectiveTemplatePhaseOneBSeeder extends Seeder
{
    public function run(): void
    {
        $type = LetterType::query()->where('code', 'SPT')->firstOrFail();
        if (LetterTemplate::query()->where('code', SptCollectiveDocumentService::TEMPLATE_CODE)->exists()) {
            $this->command?->info('Template kolektif Phase 1B sudah tersedia; dilewati.');
            return;
        }
        $actor = User::query()->orderBy('id')->value('id');
        if (! $actor) throw new RuntimeException('Akun admin belum tersedia.');
        LetterTemplate::create([
            'letter_type_id' => $type->id,
            'letterhead_profile_id' => null,
            'name' => 'SPT Bawaan - Kolektif + Lampiran Resmi (Phase 1B)',
            'code' => SptCollectiveDocumentService::TEMPLATE_CODE,
            'content_html' => '<p style="text-align:center"><strong><u>SURAT TUGAS</u></strong><br>Nomor: {{nomor_surat}}</p>'
                .'<p><strong>Menimbang</strong> : {{pertimbangan}}</p><p><strong>Dasar</strong> : {{dasar}}</p>'
                .'<p style="text-align:center"><strong>MEMBERI TUGAS :</strong></p>'
                .'<p><strong>Kepada</strong> : Daftar personil terlampir.</p>'
                .'<p><strong>Untuk</strong> : {{kegiatan}}<br>Lokasi: {{lokasi}}<br>Waktu: {{periode}}</p>'
                .'<p>Demikian surat tugas ini dibuat untuk dilaksanakan dengan sebaik-baiknya dan penuh tanggung jawab.</p>'
                .'<p style="text-align:right">{{kota_surat}}, {{tanggal_surat}}<br>{{jabatan_penandatangan}}<br><br><br><strong>{{nama_penandatangan}}</strong><br>{{nip_penandatangan}}</p>',
            'version' => 1, 'is_default' => false, 'is_active' => true,
            'created_by' => $actor, 'updated_by' => $actor,
        ]);
    }
}
