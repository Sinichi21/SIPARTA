<?php

namespace Tests\Feature;

use App\Models\OutgoingLetter;
use App\Models\Personnel;
use App\Services\OutgoingLetterTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PhaseSixteenSptOutgoingTest extends TestCase
{
    use RefreshDatabase;

    public function test_personnel_placeholder_renders_table(): void
    {
        $person = Personnel::create([
            'name' => 'Pegawai Contoh',
            'nip' => '199901012026011001',
            'position' => 'Pengendali Frekuensi Radio',
            'is_active' => true,
        ]);

        $letter = new OutgoingLetter([
            'content_html' => '<p>Menugaskan:</p>{{personil}}',
            'recipient' => 'Personil yang ditugaskan',
            'subject' => 'SPT Contoh',
            'nature' => 'biasa',
        ]);

        $letter->setRelation('personnels', collect([$person]));
        $letter->setRelation('sourceSpt', null);
        $letter->setRelation('letterheadProfile', null);

        $html = (string) app(OutgoingLetterTemplateRenderer::class)
            ->render($letter, true, true);

        $this->assertStringContainsString('<th', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('NIP', $html);
        $this->assertStringContainsString('Jabatan', $html);
        $this->assertStringContainsString('Pegawai Contoh', $html);
        $this->assertStringContainsString('199901012026011001', $html);
    }

    public function test_letterhead_layout_uses_correct_alignment(): void
    {
        $one = Blade::render(
            '<x-official-letterhead :snapshot="$s" />',
            ['s' => [
                'organization_name' => 'Balmon',
                'logo_data_uri' => 'data:image/png;base64,AAAA',
                'logo_secondary_data_uri' => null,
            ]]
        );

        $two = Blade::render(
            '<x-official-letterhead :snapshot="$s" />',
            ['s' => [
                'organization_name' => 'Balmon',
                'logo_data_uri' => 'data:image/png;base64,AAAA',
                'logo_secondary_data_uri' => 'data:image/png;base64,BBBB',
            ]]
        );

        // Tidak menggunakan garis pemisah vertikal.
        $this->assertStringNotContainsString(
            'border-right: 1px solid #64748b',
            $one
        );

        $this->assertStringNotContainsString(
            'border-right: 1px solid #64748b',
            $two
        );

        // Satu logo: teks rata kiri.
        $this->assertStringContainsString(
            'text-align: left',
            $one
        );

        // Dua logo: teks rata tengah.
        $this->assertStringContainsString(
            'text-align: center',
            $two
        );

        // Kedua mode memiliki garis atas tipis
        // dan garis bawah tebal.
        foreach ([$one, $two] as $html) {
            $this->assertStringContainsString(
                'border-top: 1px solid #555555',
                $html
            );

            $this->assertStringContainsString(
                'border-top: 3px solid #555555',
                $html
            );
        }
    }
}
