<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->string('numbering_mode', 20)->default('auto')->after('number');
            $table->string('manual_number')->nullable()->after('numbering_mode');
            $table->string('date_mode', 20)->default('auto')->after('letter_date');
            $table->date('manual_letter_date')->nullable()->after('date_mode');
        });

        Schema::create('outgoing_letter_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letter_number_sequences');

        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->dropColumn([
                'numbering_mode',
                'manual_number',
                'date_mode',
                'manual_letter_date',
            ]);
        });
    }
};
