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
        Schema::create('letter_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('letter_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('original_name');

            $table->string('stored_name');

            $table->string('path');

            $table->string('mime_type', 150)
                ->nullable();

            $table->unsignedBigInteger('size')
                ->nullable();

            $table->char('checksum_sha256', 64)
                ->nullable();

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_attachments');
    }
};
