<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issued_letters', function (Blueprint $table) {
            $table->timestamp('revoked_at')
                ->nullable()
                ->index()
                ->after('last_verified_at');

            $table->foreignId('revoked_by')
                ->nullable()
                ->after('revoked_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('revocation_reason')
                ->nullable()
                ->after('revoked_by');
        });
    }

    public function down(): void
    {
        Schema::table('issued_letters', function (Blueprint $table) {
            $table->dropForeign(['revoked_by']);
            $table->dropIndex(['revoked_at']);

            $table->dropColumn([
                'revoked_at',
                'revoked_by',
                'revocation_reason',
            ]);
        });
    }
};
