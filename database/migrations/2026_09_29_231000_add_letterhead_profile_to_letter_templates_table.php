<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_templates', function (Blueprint $table) {
            $table->foreignId('letterhead_profile_id')
                ->nullable()
                ->after('letter_type_id')
                ->constrained('letterhead_profiles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('letter_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'letterhead_profile_id'
            );
        });
    }
};
