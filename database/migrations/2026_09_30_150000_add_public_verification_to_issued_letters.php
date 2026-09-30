<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issued_letters', function (Blueprint $table) {
            $table->string('verification_code', 48)
                ->nullable()
                ->unique()
                ->after('checksum_sha256');

            $table->string('file_sha256', 64)
                ->nullable()
                ->after('pdf_name');

            $table->unsignedBigInteger('file_size')
                ->nullable()
                ->after('file_sha256');

            $table->unsignedBigInteger('verification_count')
                ->default(0)
                ->after('archived_document_at');

            $table->timestamp('last_verified_at')
                ->nullable()
                ->after('verification_count');
        });
    }

    public function down(): void
    {
        Schema::table('issued_letters', function (Blueprint $table) {
            $table->dropUnique(['verification_code']);

            $table->dropColumn([
                'verification_code',
                'file_sha256',
                'file_size',
                'verification_count',
                'last_verified_at',
            ]);
        });
    }
};
