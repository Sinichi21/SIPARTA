<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_letters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('letter_type_id')
                ->nullable()
                ->constrained('letter_types')
                ->nullOnDelete();

            $table->foreignId('letter_template_id')
                ->nullable()
                ->constrained('letter_templates')
                ->nullOnDelete();

            $table->foreignId('letterhead_profile_id')
                ->nullable()
                ->constrained('letterhead_profiles')
                ->nullOnDelete();

            $table->string('number')->nullable()->unique();
            $table->date('letter_date')->nullable()->index();
            $table->string('recipient')->index();
            $table->text('subject');
            $table->string('classification')->nullable()->index();
            $table->string('nature')->default('biasa')->index();
            $table->longText('content_html')->nullable();
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();

            foreach ([
                'created_by',
                'updated_by',
                'verified_by',
                'approved_by',
                'numbered_by',
                'published_by',
                'sent_by',
            ] as $column) {
                $table->foreignId($column)
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('numbered_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letters');
    }
};
