<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('issued_letters', function (Blueprint $table) {
            $table->string('annex_pdf_path')->nullable();
            $table->string('annex_pdf_name')->nullable();
            $table->string('annex_file_sha256', 64)->nullable();
            $table->unsignedBigInteger('annex_file_size')->nullable();
        });
    }
    public function down(): void {
        Schema::table('issued_letters', function (Blueprint $table) {
            $table->dropColumn(['annex_pdf_path', 'annex_pdf_name', 'annex_file_sha256', 'annex_file_size']);
        });
    }
};
