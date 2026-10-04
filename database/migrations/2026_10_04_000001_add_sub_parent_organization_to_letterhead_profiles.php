<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('letterhead_profiles', function (Blueprint $table) {
            $table->string('sub_parent_organization')->nullable()->after('parent_organization');
        });
    }
    public function down(): void {
        Schema::table('letterhead_profiles', function (Blueprint $table) {
            $table->dropColumn('sub_parent_organization');
        });
    }
};
