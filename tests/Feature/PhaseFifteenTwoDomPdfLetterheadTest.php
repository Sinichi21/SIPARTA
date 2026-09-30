<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PhaseFifteenTwoDomPdfLetterheadTest extends TestCase
{
    public function test_letterhead_has_explicit_dompdf_safe_columns(): void
    {
        $html = Blade::render(
            '<x-official-letterhead :snapshot="$snapshot" />',
            [
                'snapshot' => [
                    'parent_organization' => 'Kementerian Komunikasi dan Digital Republik Indonesia',
                    'organization_name' => 'Balai Monitor Spektrum Frekuensi Radio dan Infrastruktur Digital',
                    'address' => 'Jl. Kamboja Banjar Denkayu Delodan',
                    'phone' => '0361 - 88035 / 8808036',
                    'email' => 'upt_denpasar@postel.go.id',
                    'website' => null,
                    'logo_data_uri' => 'data:image/png;base64,AAAA',
                    'logo_secondary_data_uri' => null,
                ],
            ]
        );

        // Phase 16 intentionally removes the empty right image column.
        $this->assertStringContainsString('width="15%"', $html);
        $this->assertStringContainsString('width="85%"', $html);
        $this->assertStringContainsString('<colgroup>', $html);
        $this->assertStringContainsString('table-layout: fixed', $html);
        $this->assertStringContainsString('border-bottom: 3px double #0f172a', $html);
        $this->assertStringNotContainsString('width="70%"', $html);
    }

    public function test_two_images_use_grouped_image_column_and_separator(): void
    {
        $html = Blade::render(
            '<x-official-letterhead :snapshot="$snapshot" />',
            [
                'snapshot' => [
                    'organization_name' => 'Balmon',
                    'logo_data_uri' => 'data:image/png;base64,AAAA',
                    'logo_secondary_data_uri' => 'data:image/png;base64,BBBB',
                ],
            ]
        );

        $this->assertStringContainsString('width="24%"', $html);
        $this->assertStringContainsString('width="76%"', $html);
        $this->assertStringContainsString('border-right: 1px solid #64748b', $html);
    }
}
