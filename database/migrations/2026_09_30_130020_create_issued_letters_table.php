<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issued_letters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('outgoing_letter_id')
                ->unique()
                ->constrained('outgoing_letters')
                ->cascadeOnDelete();

            $table->foreignId('letter_type_id')
                ->nullable()
                ->constrained('letter_types')
                ->nullOnDelete();

            $table->string('number')->unique();
            $table->date('letter_date')->index();
            $table->text('subject');
            $table->string('recipient');
            $table->string('signatory_name')->nullable();
            $table->string('signatory_nip')->nullable();
            $table->string('signatory_position')->nullable();
            $table->timestamp('issued_at')->index();

            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status')->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_letters');
    }
};
