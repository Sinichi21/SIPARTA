<?php

namespace Tests\Feature;

use App\Livewire\PersonnelRecap\Index as PersonnelRecap;
use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\User;
use App\Services\LetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SptPersonnelScopeIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private LetterType $sptType;
    private ActivityType $activityType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->sptType = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $this->activityType = ActivityType::create([
            'code' => 'MONITORING',
            'name' => 'Monitoring Spektrum Frekuensi Radio',
            'is_active' => true,
        ]);
    }

    private function sptData(array $overrides = []): array
    {
        return array_merge([
            'activity_type_id' => $this->activityType->id,
            'number' => 'SPT-TEST-001',
            'subject' => 'Monitoring frekuensi wilayah Denpasar',
            'letter_date' => today()->toDateString(),
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'location' => 'Denpasar',
            'basis' => null,
            'description' => null,
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
            'personnel_ids' => [],
        ], $overrides);
    }

    public function test_all_personnel_scope_is_created_without_fake_or_pivot_personnel(): void
    {
        $service = app(LetterService::class);

        $letter = $service->createSpt(
            $this->sptData([
                'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
                'personnel_ids' => [],
            ]),
            $this->user->id
        );

        $this->assertSame(Letter::PERSONNEL_SCOPE_ALL, $letter->personnel_scope);
        $this->assertTrue($letter->assignsAllPersonnel());
        $this->assertCount(0, $letter->personnels);
        $this->assertDatabaseCount('letter_personnel', 0);
        $this->assertDatabaseCount('personnels', 0);
    }

    public function test_all_personnel_scope_can_be_published_without_individual_pivots(): void
    {
        $service = app(LetterService::class);

        $letter = $service->createSpt(
            $this->sptData([
                'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
                'personnel_ids' => [],
            ]),
            $this->user->id
        );

        $approved = app(\App\Services\SptReviewService::class)->approve(app(\App\Services\SptReviewService::class)->verify(app(\App\Services\SptSubmissionService::class)->submit($letter, $this->user->id), $this->user->id), $this->user->id);
        $published = $service->publish($approved, $this->user->id);

        $this->assertSame('published', $published->status->value);
        $this->assertSame(Letter::PERSONNEL_SCOPE_ALL, $published->personnel_scope);
        $this->assertSame(0, $published->personnels()->count());
    }

    public function test_selected_scope_cannot_be_published_without_personnel(): void
    {
        $service = app(LetterService::class);

        $letter = $service->createSpt(
            $this->sptData([
                'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
                'personnel_ids' => [],
            ]),
            $this->user->id
        );

        // Force an approved fixture to verify the defense-in-depth personnel guard.
        $letter->forceFill(['status' => \App\Enums\LetterStatus::Approved])->save();
        try {
            $service->publish($letter, $this->user->id);
            $this->fail('SPT scope selected tanpa personil seharusnya tidak dapat diterbitkan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('personnel_ids', $exception->errors());
        }

        $this->assertSame('approved', $letter->fresh()->status->value);
    }

    public function test_personnel_recap_separates_individual_and_all_personnel_spt(): void
    {
        $person = Personnel::create([
            'name' => 'Personil Uji',
            'is_active' => true,
        ]);

        $individual = Letter::create([
            'letter_type_id' => $this->sptType->id,
            'activity_type_id' => $this->activityType->id,
            'number' => 'SPT-INDIVIDUAL',
            'subject' => 'Monitoring individual',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Denpasar',
            'status' => 'published',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
            'created_by' => $this->user->id,
        ]);
        $individual->personnels()->attach($person);

        $allPersonnel = Letter::create([
            'letter_type_id' => $this->sptType->id,
            'activity_type_id' => $this->activityType->id,
            'number' => 'SPT-ALL',
            'subject' => 'Kegiatan seluruh pegawai',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Denpasar',
            'status' => 'published',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
            'created_by' => $this->user->id,
        ]);

        $component = app(PersonnelRecap::class);
        $component->year = (string) today()->year;

        $data = $component->render()->getData();

        $row = $data['personnels']
            ->getCollection()
            ->firstWhere('id', $person->id);

        $this->assertNotNull($row);
        $this->assertSame(1, $row->spt_count);
        $this->assertSame('SPT-INDIVIDUAL', $row->latest_spt?->number);
        $this->assertSame(1, $data['allPersonnelSpt']);

        $component->openAllPersonnelSpt();
        $drawerData = $component->render()->getData();

        $this->assertCount(1, $drawerData['allPersonnelSptRows']);
        $this->assertSame($allPersonnel->id, $drawerData['allPersonnelSptRows']->first()->id);
    }

    public function test_spt_scope_excludes_other_letter_types(): void
    {
        $otherType = LetterType::create([
            'code' => 'UND',
            'name' => 'Undangan',
            'is_active' => true,
        ]);

        $spt = Letter::create([
            'letter_type_id' => $this->sptType->id,
            'number' => 'SPT-SCOPE',
            'subject' => 'SPT',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'status' => 'published',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
            'created_by' => $this->user->id,
        ]);

        Letter::create([
            'letter_type_id' => $otherType->id,
            'number' => 'UND-001',
            'subject' => 'Undangan',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'status' => 'published',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
            'created_by' => $this->user->id,
        ]);

        $this->assertSame([$spt->id], Letter::query()->spt()->pluck('id')->all());
    }
}
