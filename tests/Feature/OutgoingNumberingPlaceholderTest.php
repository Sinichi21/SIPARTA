<?php

namespace Tests\Feature;

use App\Models\LetterType;
use App\Models\OutgoingLetterNumberSequence;
use App\Services\OutgoingLetterNumberService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingNumberingPlaceholderTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbering_continues_from_existing_sequence_and_renders_custom_placeholder(): void
    {
        $type = LetterType::create([
            'code' => 'UND',
            'name' => 'Undangan',
            'numbering_pattern' => '{sequence}/{tipe_surat}/{month_roman}/{year}',
            'is_active' => true,
        ]);

        OutgoingLetterNumberSequence::create([
            'year' => 2026,
            'last_number' => 246,
        ]);

        $number = app(OutgoingLetterNumberService::class)->next(
            Carbon::parse('2026-10-01'),
            $type,
            ['tipe_surat' => 'B']
        );

        $this->assertSame('247/B/X/2026', $number);
        $this->assertDatabaseHas('outgoing_letter_number_sequences', [
            'year' => 2026,
            'last_number' => 247,
        ]);
    }
}
