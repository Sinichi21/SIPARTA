<?php
namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneELinkedNumberContractTest extends TestCase
{
    public function test_linked_spt_publish_uses_the_source_number_and_date(): void
    {
        $source = file_get_contents(app_path('Services/OutgoingLetterService.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString('Linked SPT must reuse the source', $source);
        $this->assertStringContainsString('$finalNumber = trim((string) $source->number);', $source);
        $this->assertStringContainsString('$finalDate = $source->letter_date->toDateString();', $source);
        $this->assertStringContainsString('End normal (non-linked) numbering', $source);
    }
}
