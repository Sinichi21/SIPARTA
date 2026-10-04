<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PhaseFifteenTwoLetterheadConsistencyTest extends TestCase
{
    public function test_official_letterhead_uses_stable_print_safe_markup(): void
    {
        $html = Blade::render(
            '<x-official-letterhead :snapshot="$snapshot" />',
            [
                'snapshot' => [
                    'parent_organization' => 'Kementerian Komunikasi dan Digital RI | Direktorat Jenderal Infrastruktur Digital',
                    'organization_name' => 'Balai Monitor Spektrum Frekuensi Radio dan Infrastruktur Digital Kelas I Denpasar',
                    'address' => 'Jl. Kamboja Banjar Denkayu Delodan, Badung, Bali, 80351',
                    'phone' => '(0361) 880836',
                    'email' => 'upt_denpasar@postel.go.id',
                    'website' => null,
                    'logo_data_uri' => 'data:image/png;base64,AAAA',
                    'logo_secondary_data_uri' => null,
                ],
            ]
        );

        $this->assertStringContainsString('border-top: 1px solid #555555', $html);
        $this->assertStringContainsString('data:image/png;base64,AAAA', $html);
        $this->assertStringContainsString('Kementerian Komunikasi dan Digital RI', $html);
        $this->assertStringContainsString('Direktorat Jenderal Infrastruktur Digital', $html);
        $this->assertStringContainsString('Balai Monitor Spektrum Frekuensi Radio dan Infrastruktur Digital Kelas I Denpasar', $html);
        $this->assertStringContainsString('(0361) 880836', $html);
        $this->assertStringContainsString('upt_denpasar@postel.go.id', $html);
    }

    public function test_all_letter_outputs_reference_the_same_letterhead_component(): void
    {
        $files = [
            resource_path('views/components/letterhead-preview.blade.php'),
            resource_path('views/letters/document-body.blade.php'),
            resource_path('views/livewire/outgoing-letters/partials/a4-preview.blade.php'),
            resource_path('views/issued-letters/pdf.blade.php'),
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file);

            $this->assertStringContainsString(
                'official-letterhead',
                file_get_contents($file),
                $file
            );
        }
    }
}
