<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->foreignId('source_spt_id')->nullable()->after('id')
                ->constrained('letters')->nullOnDelete();
            $table->unique('source_spt_id');
        });

        Schema::create('outgoing_letter_personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outgoing_letter_id')->constrained('outgoing_letters')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['outgoing_letter_id','personnel_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('outgoing_letter_personnel');
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->dropForeign(['source_spt_id']);
            $table->dropUnique(['source_spt_id']);
            $table->dropColumn('source_spt_id');
        });
    }
};
