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

    public function test_two_images_add_separator_but_one_image_does_not(): void
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

        $this->assertStringNotContainsString(
            'border-right: 1px solid #64748b',
            $one
        );

        $this->assertStringContainsString(
            'border-right: 1px solid #64748b',
            $two
        );
    }
}
