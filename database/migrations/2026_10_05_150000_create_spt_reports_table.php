<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spt_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('draft');
            $table->text('activity_summary');
            $table->text('results');
            $table->text('obstacles')->nullable();
            $table->text('follow_up')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spt_reports');
    }
};
