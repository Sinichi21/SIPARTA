<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('letter_personnel', function (Blueprint $table) {
            $table->id();

            $table->foreignId('letter_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('personnel_id')
                ->constrained('personnels')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique([
                'letter_id',
                'personnel_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_personnel');
    }
};
