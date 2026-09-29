<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letterhead_profiles', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('organization_name');
            $table->string('parent_organization')->nullable();

            $table->text('address')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('city', 120)->nullable();

            $table->string('logo_path')->nullable();
            $table->string('logo_original_name')->nullable();

            $table->string('signatory_name')->nullable();
            $table->string('signatory_nip', 100)->nullable();
            $table->string('signatory_position')->nullable();

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['is_active', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letterhead_profiles');
    }
};
