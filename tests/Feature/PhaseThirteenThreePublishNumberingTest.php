<?php

namespace Tests\Feature;

use App\Enums\OutgoingLetterStatus;
use App\Models\IssuedLetter;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterNumberSequence;
use App\Models\User;
use App\Services\OutgoingLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseThirteenThreePublishNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_number_is_not_consumed_before_publish(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $type = LetterType::create([
            'code' => 'UND',
            'name' => 'Undangan',
            'numbering_pattern' => '{sequence_padded}/{type}/{month_roman}/{year}',
            'requires_personnel' => false,
            'is_active' => true,
        ]);

        $letter = OutgoingLetter::create([
            'letter_type_id' => $type->id,
            'recipient' => 'Penerima',
            'subject' => 'Undangan',
            'nature' => 'biasa',
            'numbering_mode' => 'auto',
            'date_mode' => 'auto',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $service = app(OutgoingLetterService::class);

        $service->verify($letter, $user);
        $letter->refresh();

        $service->approve($letter, $user);
        $letter->refresh();

        $this->assertNull($letter->number);
        $this->assertNull($letter->letter_date);
        $this->assertDatabaseCount('outgoing_letter_number_sequences', 0);

        $service->publish($letter, $user);

        $letter->refresh();

        $this->assertSame(
            OutgoingLetterStatus::Published,
            $letter->status
        );

        $this->assertNotNull($letter->number);
        $this->assertNotNull($letter->letter_date);

        $this->assertSame(
            1,
            (int) OutgoingLetterNumberSequence::query()
                ->where('year', (int) $letter->letter_date->year)
                ->value('last_number')
        );

        $this->assertDatabaseHas('issued_letters', [
            'outgoing_letter_id' => $letter->id,
            'number' => $letter->number,
        ]);
    }

    public function test_manual_number_does_not_consume_auto_sequence(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $type = LetterType::create([
            'code' => 'ND',
            'name' => 'Nota Dinas',
            'numbering_pattern' => '{sequence_padded}/{type}/{year}',
            'requires_personnel' => false,
            'is_active' => true,
        ]);

        $letter = OutgoingLetter::create([
            'letter_type_id' => $type->id,
            'recipient' => 'Penerima',
            'subject' => 'Nota',
            'nature' => 'biasa',
            'numbering_mode' => 'manual',
            'manual_number' => 'MANUAL/001/2026',
            'date_mode' => 'manual',
            'manual_letter_date' => '2026-09-30',
            'status' => 'approved',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(OutgoingLetterService::class)
            ->publish($letter, $user);

        $letter->refresh();

        $this->assertSame(
            'MANUAL/001/2026',
            $letter->number
        );

        $this->assertSame(
            '2026-09-30',
            $letter->letter_date->toDateString()
        );

        $this->assertDatabaseCount(
            'outgoing_letter_number_sequences',
            0
        );
    }

    public function test_duplicate_manual_number_is_rejected_again_at_publish(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        OutgoingLetter::create([
            'number' => 'DUP/001/2026',
            'letter_date' => '2026-09-30',
            'recipient' => 'A',
            'subject' => 'Sudah terbit',
            'nature' => 'biasa',
            'numbering_mode' => 'manual',
            'date_mode' => 'manual',
            'status' => 'published',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $candidate = OutgoingLetter::create([
            'recipient' => 'B',
            'subject' => 'Kandidat',
            'nature' => 'biasa',
            'numbering_mode' => 'manual',
            'manual_number' => 'DUP/001/2026',
            'date_mode' => 'manual',
            'manual_letter_date' => '2026-09-30',
            'status' => 'approved',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->expectException(
            ValidationException::class
        );

        app(OutgoingLetterService::class)
            ->publish($candidate, $user);
    }
}
