<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'personnel_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('personnel_id')
                    ->nullable()
                    ->constrained('personnels')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('users', 'must_set_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('must_set_password')
                    ->default(false);
            });
        }

        if (! Schema::hasColumn('users', 'activation_sent_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('activation_sent_at')
                    ->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'invited_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('invited_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'invited_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('invited_by');
            });
        }

        if (Schema::hasColumn('users', 'activation_sent_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('activation_sent_at');
            });
        }

        if (Schema::hasColumn('users', 'must_set_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('must_set_password');
            });
        }

        if (Schema::hasColumn('users', 'personnel_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('personnel_id');
            });
        }
    }
};
