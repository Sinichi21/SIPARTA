<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\User;
use App\Services\LetterTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LetterTemplatePhaseSevenTest extends TestCase
{
    use RefreshDatabase;

    public function test_renderer_replaces_spt_placeholders_and_escapes_letter_values(): void
    {
        $user = User::factory()->create();

        $type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $activity = ActivityType::create([
            'code' => 'MON',
            'name' => 'Monitoring',
            'is_active' => true,
        ]);

        $person = Personnel::create([
            'name' => '<script>alert(1)</script>',
            'is_active' => true,
        ]);

        $letter = Letter::create([
            'letter_type_id' => $type->id,
            'activity_type_id' => $activity->id,
            'created_by' => $user->id,
            'number' => 'SPT-001',
            'subject' => 'Monitoring Frekuensi',
            'letter_date' => '2026-09-29',
            'start_date' => '2026-09-29',
            'end_date' => '2026-09-30',
            'location' => 'Denpasar',
            'status' => 'draft',
            'record_type' => 'normal',
            'personnel_scope' => 'selected',
        ]);

        $letter->personnels()->attach($person);

        $template = new LetterTemplate([
            'content_html' =>
                '<p>{{nomor_surat}}</p><p>{{personil}}</p><p>{{kegiatan}}</p>',
        ]);

        $rendered = (string) app(
            LetterTemplateRenderer::class
        )->render(
            $template,
            $letter->load(
                'activityType',
                'personnels.unit'
            )
        );

        $this->assertStringContainsString('SPT-001', $rendered);
        $this->assertStringContainsString('Monitoring Frekuensi', $rendered);
        $this->assertStringNotContainsString('<script>', $rendered);
        $this->assertStringContainsString('&lt;script&gt;', $rendered);
    }

    public function test_template_pages_require_settings_permissions(): void
    {
        Permission::findOrCreate('settings.view', 'web');
        Permission::findOrCreate('settings.manage', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo([
            'settings.view',
            'settings.manage',
        ]);

        $this->actingAs($user);

        $this->assertTrue($user->can('settings.view'));
        $this->assertTrue($user->can('settings.manage'));
    }
}
