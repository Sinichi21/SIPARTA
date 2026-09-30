<?php

namespace Tests\Feature;

use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\IssuedLetterVerificationService;
use App\Services\OutgoingLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseFifteenPublicVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_letter_gets_public_verification_code_and_file_hash(): void
    {
        Storage::fake();

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
            'subject' => 'Undangan Verifikasi',
            'nature' => 'biasa',
            'content_html' => '<p>Nomor: {{ nomor_surat }}</p><p>Yth. {{ tujuan }}</p>',
            'numbering_mode' => 'manual',
            'manual_number' => '001/UND/IX/2026',
            'date_mode' => 'manual',
            'manual_letter_date' => '2026-09-30',
            'status' => 'approved',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $issued = app(OutgoingLetterService::class)
            ->publish($letter, $user)
            ->fresh();

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{48}$/',
            (string) $issued->verification_code
        );

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            (string) $issued->file_sha256
        );

        $this->assertGreaterThan(
            0,
            (int) $issued->file_size
        );

        Storage::assertExists($issued->pdf_path);
    }

    public function test_public_verification_page_increments_counter(): void
    {
        Storage::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $letter = OutgoingLetter::create([
            'recipient' => 'Penerima',
            'subject' => 'Surat Verifikasi Publik',
            'nature' => 'biasa',
            'content_html' => '<p>Surat verifikasi.</p>',
            'numbering_mode' => 'manual',
            'manual_number' => 'PUBLIC/001/2026',
            'date_mode' => 'manual',
            'manual_letter_date' => '2026-09-30',
            'status' => 'approved',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $issued = app(OutgoingLetterService::class)
            ->publish($letter, $user)
            ->fresh();

        auth()->logout();

        $this->get(route(
            'issued-letters.verify',
            ['code' => $issued->verification_code]
        ))
            ->assertOk()
            ->assertSee('Dokumen terdaftar sebagai surat resmi')
            ->assertSee('PUBLIC/001/2026');

        $this->assertSame(
            1,
            (int) $issued->fresh()->verification_count
        );
    }

    public function test_archived_pdf_integrity_is_checked_before_download(): void
    {
        Storage::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $letter = OutgoingLetter::create([
            'recipient' => 'Penerima',
            'subject' => 'Uji Integritas',
            'nature' => 'biasa',
            'content_html' => '<p>Uji integritas.</p>',
            'numbering_mode' => 'manual',
            'manual_number' => 'HASH/001/2026',
            'date_mode' => 'manual',
            'manual_letter_date' => '2026-09-30',
            'status' => 'approved',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $issued = app(OutgoingLetterService::class)
            ->publish($letter, $user)
            ->fresh();

        Storage::put(
            $issued->pdf_path,
            'binary-yang-sudah-diubah'
        );

        $this->expectException(
            ValidationException::class
        );

        app(IssuedLetterVerificationService::class)
            ->archivedPdfBinary($issued);
    }
}
