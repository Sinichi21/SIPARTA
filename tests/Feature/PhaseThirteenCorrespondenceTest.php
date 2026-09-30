<?php

namespace Tests\Feature;

use App\Enums\IncomingLetterStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\IncomingLetterService;
use App\Services\OutgoingLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseThirteenCorrespondenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_letter_follows_expected_workflow(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $letter = IncomingLetter::create([
            'agenda_number' => 'SM/2026/0001',
            'received_date' => '2026-09-30',
            'sender' => 'Instansi Pengirim',
            'subject' => 'Permohonan informasi',
            'nature' => 'biasa',
            'status' => 'recorded',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $service = app(IncomingLetterService::class);

        $service->dispose($letter, $user);
        $this->assertSame(
            IncomingLetterStatus::Disposed,
            $letter->fresh()->status
        );

        $letter->refresh();
        $service->process($letter, $user);
        $this->assertSame(
            IncomingLetterStatus::Processing,
            $letter->fresh()->status
        );

        $letter->refresh();
        $service->complete($letter, $user);
        $this->assertSame(
            IncomingLetterStatus::Completed,
            $letter->fresh()->status
        );

        $letter->refresh();
        $service->archive($letter, $user);
        $this->assertSame(
            IncomingLetterStatus::Archived,
            $letter->fresh()->status
        );
    }

    public function test_publishing_outgoing_letter_creates_issued_register_snapshot(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $type = LetterType::create([
            'code' => 'UND',
            'name' => 'Undangan',
            'requires_personnel' => false,
            'is_active' => true,
        ]);

        $letter = OutgoingLetter::create([
            'letter_type_id' => $type->id,
            'recipient' => 'Tujuan Surat',
            'subject' => 'Undangan Kegiatan',
            'nature' => 'biasa',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $service = app(OutgoingLetterService::class);

        $service->verify($letter, $user);
        $letter->refresh();

        $service->approve($letter, $user);
        $letter->refresh();

        $service->number(
            $letter,
            $user,
            '001/UND/IX/2026',
            '2026-09-30'
        );
        $letter->refresh();

        $issued = $service->publish(
            $letter,
            $user
        );

        $this->assertSame(
            OutgoingLetterStatus::Published,
            $letter->fresh()->status
        );

        $this->assertDatabaseHas(
            'issued_letters',
            [
                'id' => $issued->id,
                'outgoing_letter_id' => $letter->id,
                'number' => '001/UND/IX/2026',
                'recipient' => 'Tujuan Surat',
            ]
        );
    }
}
