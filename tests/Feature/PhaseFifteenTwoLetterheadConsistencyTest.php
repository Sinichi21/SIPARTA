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
                    'parent_organization' => 'Kementerian Komunikasi dan Digital',
                    'organization_name' => 'Balai Monitor Spektrum Frekuensi Radio',
                    'address' => 'Jl. Contoh No. 1',
                    'phone' => '0361-123456',
                    'email' => 'contoh@example.go.id',
                    'website' => 'example.go.id',
                    'logo_data_uri' => 'data:image/png;base64,AAAA',
                    'logo_secondary_data_uri' => null,
                ],
            ]
        );

        $this->assertStringContainsString(
            'border-bottom: 3px double #0f172a',
            $html
        );

        $this->assertStringContainsString(
            'data:image/png;base64,AAAA',
            $html
        );

        $this->assertStringContainsString(
            'Kementerian Komunikasi dan Digital',
            $html
        );

        $this->assertStringContainsString(
            'Balai Monitor Spektrum Frekuensi Radio',
            $html
        );

        $this->assertStringContainsString(
            '0361-123456',
            $html
        );
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
