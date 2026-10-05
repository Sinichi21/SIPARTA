<?php

namespace Tests\Feature;

use App\Models\LetterTemplate;
use App\Models\LetterType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshDefaultSptTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_updates_only_active_default_spt_template(): void
    {
        $type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $default = LetterTemplate::create([
            'letter_type_id' => $type->id,
            'name' => 'Default',
            'code' => 'SPT_DEFAULT',
            'content_html' => '<p>Lama</p>',
            'version' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);

        $other = LetterTemplate::create([
            'letter_type_id' => $type->id,
            'name' => 'Alternatif',
            'code' => 'SPT_ALT',
            'content_html' => '<p>Jangan diubah</p>',
            'version' => 3,
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->artisan('spt:refresh-default-template', ['--force' => true])
            ->expectsOutputToContain('Template default SPT berhasil diperbarui.')
            ->assertExitCode(0);

        $default->refresh();
        $other->refresh();

        $this->assertSame(2, $default->version);
        $this->assertStringContainsString('border:0', $default->content_html);
        $this->assertStringContainsString('{{ kegiatan }}', $default->content_html);
        $this->assertStringContainsString('{{ lokasi }}', $default->content_html);
        $this->assertStringContainsString('{{ periode }}', $default->content_html);
        $this->assertStringContainsString('{{ personil }}', $default->content_html);
        $this->assertSame('<p>Jangan diubah</p>', $other->content_html);
        $this->assertSame(3, $other->version);
    }
}
