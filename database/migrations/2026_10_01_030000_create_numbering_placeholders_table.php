<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_placeholders', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('label', 150);
            $table->string('type', 20)->default('select');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_placeholders');
    }
};
