<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\User;
use App\Services\LetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SptEditPersonnelScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private LetterType $type;
    private ActivityType $activity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $this->activity = ActivityType::create([
            'code' => 'MONITORING',
            'name' => 'Monitoring Spektrum Frekuensi Radio',
            'is_active' => true,
        ]);
    }

    private function letter(array $attributes = []): Letter
    {
        return Letter::create(array_merge([
            'letter_type_id' => $this->type->id,
            'activity_type_id' => $this->activity->id,
            'created_by' => $this->user->id,
            'number' => 'SPT-EDIT-001',
            'subject' => 'Monitoring Frekuensi',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Denpasar',
            'status' => 'draft',
            'source' => 'system',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
        ], $attributes));
    }

    private function payload(Letter $letter, array $overrides = []): array
    {
        return array_merge([
            'activity_type_id' => $letter->activity_type_id,
            'number' => $letter->number,
            'subject' => $letter->subject,
            'letter_date' => $letter->letter_date?->toDateString(),
            'start_date' => $letter->start_date?->toDateString(),
            'end_date' => $letter->end_date?->toDateString(),
            'location' => $letter->location,
            'basis' => $letter->basis,
            'description' => $letter->description,
            'record_type' => $letter->record_type?->value ?? 'normal',
            'personnel_scope' => $letter->personnel_scope,
            'personnel_ids' => $letter->personnels()->pluck('personnels.id')->all(),
        ], $overrides);
    }

    public function test_edit_can_change_selected_personnel_scope_to_all_and_clear_pivots(): void
    {
        $person = Personnel::create([
            'name' => 'Personil Uji',
            'is_active' => true,
        ]);

        $letter = $this->letter();
        $letter->personnels()->attach($person);

        $updated = app(LetterService::class)->updateSpt(
            $letter,
            $this->payload($letter, [
                'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
                'personnel_ids' => [],
            ]),
            $this->user->id
        );

        $this->assertSame(
            Letter::PERSONNEL_SCOPE_ALL,
            $updated->personnel_scope
        );
        $this->assertTrue($updated->assignsAllPersonnel());
        $this->assertSame(0, $updated->personnels()->count());
    }

    public function test_edit_can_change_all_scope_to_selected_personnel(): void
    {
        $person = Personnel::create([
            'name' => 'Personil Baru',
            'is_active' => true,
        ]);

        $letter = $this->letter([
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
        ]);

        $updated = app(LetterService::class)->updateSpt(
            $letter,
            $this->payload($letter, [
                'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
                'personnel_ids' => [$person->id],
            ]),
            $this->user->id
        );

        $this->assertSame(
            Letter::PERSONNEL_SCOPE_SELECTED,
            $updated->personnel_scope
        );
        $this->assertFalse($updated->assignsAllPersonnel());
        $this->assertSame(
            [$person->id],
            $updated->personnels()->pluck('personnels.id')->all()
        );
    }

    public function test_imported_spt_can_keep_previously_assigned_inactive_personnel(): void
    {
        $person = Personnel::create([
            'name' => 'Personil Lama',
            'is_active' => false,
        ]);

        $letter = $this->letter([
            'source' => 'import',
            'status' => 'published',
        ]);
        $letter->personnels()->attach($person);

        $updated = app(LetterService::class)->updateSpt(
            $letter,
            $this->payload($letter, [
                'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
                'personnel_ids' => [$person->id],
                'subject' => 'Koreksi data import',
            ]),
            $this->user->id
        );

        $this->assertSame(
            [$person->id],
            $updated->personnels()->pluck('personnels.id')->all()
        );
        $this->assertSame('Koreksi data import', $updated->subject);
    }
}
