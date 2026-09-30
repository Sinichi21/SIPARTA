<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issued_letters', function (Blueprint $table) {
            $table->json('snapshot_json')->nullable()->after('status');
            $table->string('checksum_sha256', 64)->nullable()->index()->after('snapshot_json');
            $table->string('pdf_path')->nullable()->after('checksum_sha256');
            $table->string('pdf_name')->nullable()->after('pdf_path');
            $table->timestamp('archived_document_at')->nullable()->after('pdf_name');
        });
    }

    public function down(): void
    {
        Schema::table('issued_letters', function (Blueprint $table) {
            $table->dropIndex(['checksum_sha256']);
            $table->dropColumn([
                'snapshot_json',
                'checksum_sha256',
                'pdf_path',
                'pdf_name',
                'archived_document_at',
            ]);
        });
    }
};
