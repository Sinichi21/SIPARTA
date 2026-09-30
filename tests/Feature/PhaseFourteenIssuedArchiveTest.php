<?php

namespace Tests\Feature;

use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\OutgoingLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseFourteenIssuedArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_creates_snapshot_checksum_and_archived_pdf(): void
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
            'recipient' => 'Penerima Uji',
            'subject' => 'Undangan Pengujian',
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

        $this->assertNotNull($issued->snapshot_json);
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            (string) $issued->checksum_sha256
        );

        $this->assertNotNull($issued->pdf_path);
        $this->assertNotNull($issued->archived_document_at);

        Storage::assertExists($issued->pdf_path);

        $this->assertSame(
            '001/UND/IX/2026',
            data_get($issued->snapshot_json, 'number')
        );

        $this->assertSame(
            'Undangan Pengujian',
            data_get($issued->snapshot_json, 'subject')
        );
    }
}
