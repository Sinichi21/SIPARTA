<?php

namespace Tests\Feature;

use App\Models\IncomingLetter;
use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\User;
use App\Services\IncomingLetterTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseThirteenOneIncomingTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_fields_are_created_only_for_unknown_placeholders(): void
    {
        $user = User::factory()->create();

        $type = LetterType::create([
            'code' => 'SM',
            'name' => 'Surat Masuk',
            'requires_personnel' => false,
            'is_active' => true,
        ]);

        $template = LetterTemplate::create([
            'letter_type_id' => $type->id,
            'name' => 'Template Surat Masuk',
            'code' => 'SM-DEFAULT',
            'content_html' => '
                <p>{{ nomor_surat }}</p>
                <p>{{ asal_surat }}</p>
                <p>{{ nama_penerima }}</p>
                <p>{{ jabatan_penerima }}</p>
            ',
            'version' => 1,
            'is_default' => false,
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $renderer = app(
            IncomingLetterTemplateRenderer::class
        );

        $this->assertSame(
            [
                'nama_penerima',
                'jabatan_penerima',
            ],
            $renderer->manualPlaceholders(
                $template
            )
        );
    }

    public function test_preview_replaces_known_and_manual_values_but_keeps_missing_placeholder_visible(): void
    {
        $user = User::factory()->create();

        $type = LetterType::create([
            'code' => 'SM',
            'name' => 'Surat Masuk',
            'requires_personnel' => false,
            'is_active' => true,
        ]);

        $template = LetterTemplate::create([
            'letter_type_id' => $type->id,
            'name' => 'Template Surat Masuk',
            'code' => 'SM-PREVIEW',
            'content_html' => '
                <p>{{ nomor_surat }}</p>
                <p>{{ asal_surat }}</p>
                <p>{{ nama_penerima }}</p>
                <p>{{ jabatan_penerima }}</p>
            ',
            'version' => 1,
            'is_default' => false,
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $letter = IncomingLetter::create([
            'agenda_number' => 'SM/2026/0001',
            'number' => '123/ABC/2026',
            'received_date' => '2026-09-30',
            'sender' => 'Instansi Pengirim',
            'subject' => 'Pengujian template',
            'nature' => 'biasa',
            'status' => 'recorded',
            'letter_template_id' => $template->id,
            'placeholder_data' => [
                'nama_penerima' => 'I Wayan Contoh',
            ],
        ]);

        $renderer = app(
            IncomingLetterTemplateRenderer::class
        );

        $html = (string) $renderer->render(
            $template,
            $letter
        );

        $this->assertStringContainsString(
            '123/ABC/2026',
            $html
        );

        $this->assertStringContainsString(
            'Instansi Pengirim',
            $html
        );

        $this->assertStringContainsString(
            'I Wayan Contoh',
            $html
        );

        $this->assertStringContainsString(
            '{{ jabatan_penerima }}',
            $html
        );

        $this->assertSame(
            ['jabatan_penerima'],
            $renderer->missingPlaceholders(
                $template,
                $letter
            )
        );
    }

    public function test_attachment_remains_optional(): void
    {
        $letter = IncomingLetter::create([
            'agenda_number' => 'SM/2026/0002',
            'received_date' => '2026-09-30',
            'sender' => 'Instansi Pengirim',
            'subject' => 'Tanpa file',
            'nature' => 'biasa',
            'status' => 'recorded',
        ]);

        $this->assertNull(
            $letter->original_file_path
        );

        $this->assertNull(
            $letter->original_file_name
        );
    }
}
