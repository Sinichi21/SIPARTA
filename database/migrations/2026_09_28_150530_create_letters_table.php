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
        Schema::create('letters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('letter_type_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('activity_type_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('number', 255)
                ->nullable();

            $table->string('subject', 500);

            $table->date('letter_date')
                ->nullable();

            $table->date('start_date')
                ->nullable();

            $table->date('end_date')
                ->nullable();

            $table->string('location', 500)
                ->nullable();

            $table->text('basis')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->string('status', 30)
                ->default('draft');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestampTz('approved_at')
                ->nullable();

            $table->timestampTz('published_at')
                ->nullable();

            $table->timestampTz('cancelled_at')
                ->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('cancellation_reason')
                ->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('number');
            $table->index('status');
            $table->index('letter_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
