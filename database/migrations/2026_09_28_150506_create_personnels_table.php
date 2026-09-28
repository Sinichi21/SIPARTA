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
        Schema::create('personnels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('nip', 50)
                ->nullable()
                ->unique();

            $table->string('name', 255);

            $table->string('rank', 100)
                ->nullable();

            $table->string('grade', 50)
                ->nullable();

            $table->string('position', 255)
                ->nullable();

            $table->string('email', 255)
                ->nullable();

            $table->string('phone', 30)
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnels');
    }
};
