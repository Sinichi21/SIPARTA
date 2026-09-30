<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_document_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->unique()->constrained('letters')->cascadeOnDelete();
            $table->foreignId('letter_template_id')->nullable()->constrained('letter_templates')->nullOnDelete();
            $table->string('template_name')->nullable();
            $table->string('template_code', 100)->nullable();
            $table->unsignedInteger('template_version')->nullable();
            $table->longText('rendered_html');
            $table->json('letter_snapshot');
            $table->json('letterhead_snapshot')->nullable();
            $table->char('checksum_sha256', 64);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_document_snapshots');
    }
};
