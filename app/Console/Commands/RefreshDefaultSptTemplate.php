<?php

namespace App\Console\Commands;

use App\Models\LetterTemplate;
use App\Models\LetterType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshDefaultSptTemplate extends Command
{
    protected $signature = 'spt:refresh-default-template
        {--force : Replace the current default SPT template content without confirmation}';

    protected $description = 'Rapikan template default SPT menggunakan tabel tanpa garis';

    public function handle(): int
    {
        $type = LetterType::query()->where('code', 'SPT')->first();

        if (! $type) {
            $this->error('Jenis surat SPT tidak ditemukan.');
            return self::FAILURE;
        }

        $template = LetterTemplate::query()
            ->where('letter_type_id', $type->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            $this->error('Template default SPT aktif tidak ditemukan.');
            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->info('Template: '.$template->name.' (versi '.$template->version.')');

            if (! $this->confirm('Ganti isi template default SPT dengan layout tabel tanpa border?')) {
                $this->warn('Tidak ada perubahan.');
                return self::SUCCESS;
            }
        }

        $html = <<<'HTML'
<div style="font-family:'Times New Roman',Times,serif;font-size:12pt;line-height:1.45;color:#111;">
    <div style="text-align:center;margin-bottom:12px;">
        <div style="font-weight:700;text-decoration:underline;">SURAT PERINTAH TUGAS</div>
        <div>Nomor: {{ nomor_surat }}</div>
    </div>

    <table style="width:100%;border-collapse:collapse;border:0;margin:0 0 12px;">
        <tbody>
            <tr>
                <td style="width:22%;border:0;padding:2px 0;vertical-align:top;">Dasar</td>
                <td style="width:3%;border:0;padding:2px 0;vertical-align:top;text-align:center;">:</td>
                <td style="width:75%;border:0;padding:2px 0;vertical-align:top;">{{ dasar }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin:0 0 8px;">Memberi Perintah Kepada:</p>

    <table style="width:100%;border-collapse:collapse;border:0;margin:0 0 12px;">
        <tbody>
            <tr>
                <td style="width:22%;border:0;padding:2px 0;vertical-align:top;">Personil</td>
                <td style="width:3%;border:0;padding:2px 0;vertical-align:top;text-align:center;">:</td>
                <td style="width:75%;border:0;padding:2px 0;vertical-align:top;">{{ personil }}</td>
            </tr>
            <tr>
                <td style="border:0;padding:2px 0;vertical-align:top;">Unit / Tim</td>
                <td style="border:0;padding:2px 0;vertical-align:top;text-align:center;">:</td>
                <td style="border:0;padding:2px 0;vertical-align:top;">{{ unit_tim }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin:0 0 8px;">Untuk melaksanakan tugas sebagai berikut:</p>

    <table style="width:100%;border-collapse:collapse;border:0;margin:0 0 12px;">
        <tbody>
            <tr>
                <td style="width:22%;border:0;padding:2px 0;vertical-align:top;">Kegiatan</td>
                <td style="width:3%;border:0;padding:2px 0;vertical-align:top;text-align:center;">:</td>
                <td style="width:75%;border:0;padding:2px 0;vertical-align:top;">{{ kegiatan }}</td>
            </tr>
            <tr>
                <td style="border:0;padding:2px 0;vertical-align:top;">Jenis Kegiatan</td>
                <td style="border:0;padding:2px 0;vertical-align:top;text-align:center;">:</td>
                <td style="border:0;padding:2px 0;vertical-align:top;">{{ jenis_kegiatan }}</td>
            </tr>
            <tr>
                <td style="border:0;padding:2px 0;vertical-align:top;">Lokasi</td>
                <td style="border:0;padding:2px 0;vertical-align:top;text-align:center;">:</td>
                <td style="border:0;padding:2px 0;vertical-align:top;">{{ lokasi }}</td>
            </tr>
            <tr>
                <td style="border:0;padding:2px 0;vertical-align:top;">Periode</td>
                <td style="border:0;padding:2px 0;vertical-align:top;text-align:center;">:</td>
                <td style="border:0;padding:2px 0;vertical-align:top;">{{ periode }}</td>
            </tr>
            <tr>
                <td style="border:0;padding:2px 0;vertical-align:top;">Keterangan</td>
                <td style="border:0;padding:2px 0;vertical-align:top;text-align:center;">:</td>
                <td style="border:0;padding:2px 0;vertical-align:top;">{{ keterangan }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin:12px 0 0;text-align:justify;">
        Demikian Surat Perintah Tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.
    </p>
</div>
HTML;

        DB::transaction(function () use ($template, $html): void {
            $changed = $template->content_html !== $html;

            $template->forceFill([
                'content_html' => $html,
                'version' => $changed ? $template->version + 1 : $template->version,
                'updated_by' => auth()->id(),
            ])->save();
        });

        $template->refresh();

        $this->info('Template default SPT berhasil diperbarui.');
        $this->line('Versi: '.$template->version);

        return self::SUCCESS;
    }
}
