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
        Schema::create('letter_number_sequences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('letter_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedSmallInteger('year');

            $table->unsignedInteger('last_number')
                ->default(0);

            $table->timestamps();

            $table->unique([
                'letter_type_id',
                'unit_id',
                'year',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_number_sequences');
    }
};
