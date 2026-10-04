<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('letters', function (Blueprint $table) {
            $table->text('assignment_purpose')->nullable();
            $table->string('departure_place', 500)->nullable();
            $table->string('destination_place', 500)->nullable();
            $table->string('transport_mode', 120)->nullable();
            $table->string('budget_account', 180)->nullable();
        });
    }
    public function down(): void {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn(['assignment_purpose','departure_place','destination_place','transport_mode','budget_account']);
        });
    }
};
