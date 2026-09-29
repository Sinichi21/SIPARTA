<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->string('source', 30)->default('system')->index()->after('status');
            $table->foreignId('import_batch_id')
                ->nullable()
                ->after('source')
                ->constrained('import_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('import_batch_id');
            $table->dropColumn('source');
        });
    }
};
