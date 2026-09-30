<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('letterhead_profiles', function (Blueprint $table) {
            $table->string('logo_secondary_path')->nullable()->after('logo_original_name');
            $table->string('logo_secondary_original_name')->nullable()->after('logo_secondary_path');
        });
    }
    public function down(): void {
        Schema::table('letterhead_profiles', function (Blueprint $table) {
            $table->dropColumn(['logo_secondary_path','logo_secondary_original_name']);
        });
    }
};
