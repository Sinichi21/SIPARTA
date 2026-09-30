<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->string('agenda_number')->unique();
            $table->string('number')->nullable()->index();
            $table->date('letter_date')->nullable()->index();
            $table->date('received_date')->index();
            $table->string('sender')->index();
            $table->text('subject');
            $table->string('classification')->nullable()->index();
            $table->string('nature')->default('biasa')->index();
            $table->string('attachment_note')->nullable();
            $table->string('destination')->nullable();
            $table->string('status')->default('recorded')->index();
            $table->text('notes')->nullable();
            $table->string('original_file_path')->nullable();
            $table->string('original_file_name')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('disposed_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_letters');
    }
};
