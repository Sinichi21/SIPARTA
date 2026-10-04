<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PhaseFifteenTwoDomPdfLetterheadTest extends TestCase
{
    public function test_one_logo_uses_reference_layout_with_left_aligned_text(): void
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

        $this->assertStringContainsString('width="20%"', $html);
        $this->assertStringContainsString('width="80%"', $html);
        $this->assertStringContainsString('text-align: left', $html);
        $this->assertStringContainsString('font-family: Arial, Helvetica, sans-serif', $html);
        $this->assertStringContainsString('border-top: 1px solid #555555', $html);
        $this->assertStringContainsString('Kementerian Komunikasi dan Digital RI', $html);
        $this->assertStringContainsString('Direktorat Jenderal Infrastruktur Digital', $html);
    }

    public function test_two_images_keep_grouped_logos_and_center_the_text(): void
    {
        $html = Blade::render(
            '<x-official-letterhead :snapshot="$snapshot" />',
            [
                'snapshot' => [
                    'parent_organization' => 'Kementerian Komunikasi dan Digital RI | Direktorat Jenderal Infrastruktur Digital',
                    'organization_name' => 'Balai Monitor Spektrum Frekuensi Radio dan Infrastruktur Digital Kelas I Denpasar',
                    'logo_data_uri' => 'data:image/png;base64,AAAA',
                    'logo_secondary_data_uri' => 'data:image/png;base64,BBBB',
                ],
            ]
        );

        $this->assertStringContainsString('width="28%"', $html);
        $this->assertStringContainsString('width="72%"', $html);
        $this->assertStringContainsString('text-align: center', $html);
        $this->assertStringNotContainsString('border-right:', $html);
    }
}
