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
        Schema::create('letter_types', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)
                ->unique();

            $table->string('name', 255);

            $table->text('description')
                ->nullable();

            $table->string('numbering_pattern', 255)
                ->nullable();

            $table->boolean('requires_personnel')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_types');
    }
};
