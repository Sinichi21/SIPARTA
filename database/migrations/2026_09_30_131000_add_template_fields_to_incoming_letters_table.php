<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incoming_letters', function (Blueprint $table) {
            $table->foreignId('letter_template_id')
                ->nullable()
                ->after('destination')
                ->constrained('letter_templates')
                ->nullOnDelete();

            $table->json('placeholder_data')
                ->nullable()
                ->after('letter_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('incoming_letters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('letter_template_id');
            $table->dropColumn('placeholder_data');
        });
    }
};
