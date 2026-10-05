<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spt_report_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spt_report_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->string('checksum_sha256', 64);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index(['spt_report_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spt_report_attachments');
    }
};
