<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('personnel_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_team_id')->constrained('personnel_teams')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['personnel_team_id', 'personnel_id']);
        });

        Schema::table('letters', function (Blueprint $table) {
            $table->foreignId('personnel_team_id')
                ->nullable()
                ->after('personnel_scope')
                ->constrained('personnel_teams')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('personnel_team_id');
        });

        Schema::dropIfExists('personnel_team_members');
        Schema::dropIfExists('personnel_teams');
    }
};
