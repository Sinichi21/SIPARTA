<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use App\Models\Letter;
use Tests\TestCase;

class PhaseTwoAOptionalTravelFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_optional_travel_fields_are_available_without_making_old_spt_invalid(): void
    {
        foreach (['assignment_purpose', 'departure_place', 'destination_place', 'transport_mode', 'budget_account'] as $field) {
            $this->assertTrue(Schema::hasColumn('letters', $field));
            $this->assertTrue(in_array($field, (new Letter)->getFillable(), true));
        }
    }
}
