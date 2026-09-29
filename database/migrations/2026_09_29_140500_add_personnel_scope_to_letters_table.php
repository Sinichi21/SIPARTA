<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table
                ->string('personnel_scope', 20)
                ->default('selected')
                ->after('record_type')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropIndex(['personnel_scope']);
            $table->dropColumn('personnel_scope');
        });
    }
};
