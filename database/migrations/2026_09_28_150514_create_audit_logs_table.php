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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('action', 100);

            $table->string('subject_type', 255)
                ->nullable();

            $table->unsignedBigInteger('subject_id')
                ->nullable();

            $table->jsonb('old_values')
                ->nullable();

            $table->jsonb('new_values')
                ->nullable();

            $table->ipAddress('ip_address')
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->timestampTz('created_at')
                ->useCurrent();

            $table->index([
                'subject_type',
                'subject_id',
            ]);

            $table->index([
                'user_id',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
