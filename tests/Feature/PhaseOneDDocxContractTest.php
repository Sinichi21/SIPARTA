<?php

namespace Tests\Feature;

use App\Models\IssuedLetter;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Services\IssuedSptDocxExportService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseOneDDocxContractTest extends TestCase
{
    public function test_legacy_issued_spt_without_frozen_html_rejects_docx_export(): void
    {
        $type = new LetterType(['code' => 'SPT', 'name' => 'SPT']);
        $out = new OutgoingLetter(['subject' => 'Pengujian']);
        $out->setRelation('letterType', $type);
        $issued = new IssuedLetter(['number' => '1/SPT/2026', 'snapshot_json' => []]);
        $issued->setRelation('outgoingLetter', $out);

        $this->expectException(ValidationException::class);
        app(IssuedSptDocxExportService::class)->export($issued);
    }

    public function test_docx_route_is_authenticated(): void
    {
        $route = app('router')->getRoutes()->getByName('issued-letters.spt.docx');
        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
    }
}
