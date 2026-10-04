<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Services\OutgoingLetterNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneFThreeNumberingGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_outgoing_skips_number_already_used_by_spt(): void
    {
        $type = LetterType::create(['code' => 'SPT', 'name' => 'SPT', 'numbering_pattern' => '{sequence_padded}/SPT/IX/2026', 'is_active' => true]);
        $other = LetterType::create(['code' => 'OTHER', 'name' => 'Other', 'is_active' => true]);
        // Minimal SPT fixture; use model events/data constraints of current project.
        Letter::query()->create([
            'letter_type_id' => $type->id,
            'subject' => 'Collision fixture',
            'number' => '001/SPT/IX/2026',
            'letter_date' => '2026-09-30',
            'start_date' => '2026-09-30',
            'end_date' => '2026-09-30',
            'location' => 'Denpasar',
            'status' => 'draft',
            'record_type' => 'normal',
            'created_by' => \App\Models\User::factory()->create()->id,
        ]);
        $next = app(OutgoingLetterNumberService::class)->next(\Carbon\Carbon::parse('2026-09-30'), $type);
        $this->assertSame('002/SPT/IX/2026', $next);
    }
}
