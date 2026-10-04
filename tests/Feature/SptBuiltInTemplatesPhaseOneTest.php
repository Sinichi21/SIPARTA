<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Services\SptPersonnelBlockRenderer;
use Database\Seeders\SptBuiltInTemplatesSeeder;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SptBuiltInTemplatesPhaseOneTest extends TestCase
{
    public function test_builtin_templates_never_embed_a_second_letterhead(): void
    {
        foreach (SptBuiltInTemplatesSeeder::definitions() as $template) {
            $this->assertStringNotContainsString('official-letterhead', $template['content_html']);
            $this->assertStringContainsString('{{nomor_surat}}', $template['content_html']);
        }
    }

    public function test_personnel_block_escapes_untrusted_personnel_fields(): void
    {
        $person = new Personnel(['name' => '<script>alert(1)</script>', 'nip' => '123', 'rank' => 'Penata', 'grade' => 'III/c', 'position' => 'Analis']);
        $html = (string) app(SptPersonnelBlockRenderer::class)->render(new Collection([$person]));
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('III/c', $html);
    }
}
