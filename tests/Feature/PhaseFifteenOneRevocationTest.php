<?php

namespace Tests\Feature;

use App\Models\IssuedLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\IssuedLetterRevocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseFifteenOneRevocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_issued_document_can_be_revoked_without_deleting_history(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $outgoing = OutgoingLetter::create([
            'recipient' => 'Penerima',
            'subject' => 'Surat Resmi',
            'nature' => 'biasa',
            'status' => 'published',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $issued = IssuedLetter::create([
            'outgoing_letter_id' => $outgoing->id,
            'number' => '001/TEST/2026',
            'letter_date' => '2026-09-30',
            'subject' => 'Surat Resmi',
            'recipient' => 'Penerima',
            'issued_at' => now(),
            'issued_by' => $user->id,
            'status' => 'active',
        ]);

        $result = app(IssuedLetterRevocationService::class)
            ->revoke(
                $issued,
                $user,
                'Dokumen dicabut karena terdapat koreksi administrasi.'
            );

        $this->assertTrue($result->isRevoked());
        $this->assertSame('revoked', $result->status);
        $this->assertNotNull($result->revoked_at);
        $this->assertSame($user->id, $result->revoked_by);
        $this->assertDatabaseHas('issued_letters', [
            'id' => $issued->id,
            'number' => '001/TEST/2026',
            'status' => 'revoked',
        ]);
        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $outgoing->id,
        ]);
    }

    public function test_revocation_reason_must_be_meaningful(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $outgoing = OutgoingLetter::create([
            'recipient' => 'Penerima',
            'subject' => 'Surat Resmi',
            'nature' => 'biasa',
            'status' => 'published',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $issued = IssuedLetter::create([
            'outgoing_letter_id' => $outgoing->id,
            'number' => '002/TEST/2026',
            'letter_date' => '2026-09-30',
            'subject' => 'Surat Resmi',
            'recipient' => 'Penerima',
            'issued_at' => now(),
            'issued_by' => $user->id,
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        app(IssuedLetterRevocationService::class)
            ->revoke($issued, $user, 'salah');
    }

    public function test_revocation_is_idempotent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $outgoing = OutgoingLetter::create([
            'recipient' => 'Penerima',
            'subject' => 'Surat Resmi',
            'nature' => 'biasa',
            'status' => 'published',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $issued = IssuedLetter::create([
            'outgoing_letter_id' => $outgoing->id,
            'number' => '003/TEST/2026',
            'letter_date' => '2026-09-30',
            'subject' => 'Surat Resmi',
            'recipient' => 'Penerima',
            'issued_at' => now(),
            'issued_by' => $user->id,
            'status' => 'active',
        ]);

        $service = app(IssuedLetterRevocationService::class);

        $first = $service->revoke(
            $issued,
            $user,
            'Dokumen dicabut karena terdapat koreksi administrasi.'
        );

        $second = $service->revoke(
            $first,
            $user,
            'Alasan kedua tidak seharusnya menimpa catatan awal.'
        );

        $this->assertSame(
            'Dokumen dicabut karena terdapat koreksi administrasi.',
            $second->revocation_reason
        );
    }
}
