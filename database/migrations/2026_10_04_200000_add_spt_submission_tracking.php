<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->string('submission_reference', 64)->nullable()->unique();
            $table->timestampTz('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('spt_submission_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->constrained('letters')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 40);
            $table->string('from_status', 40);
            $table->string('to_status', 40);
            $table->text('note')->nullable();
            $table->timestampsTz();
            $table->index(['letter_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('spt_submission_events');
        Schema::table('letters', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropUnique(['submission_reference']);
            $table->dropColumn(['submission_reference', 'submitted_at', 'submitted_by']);
        });
    }
};
