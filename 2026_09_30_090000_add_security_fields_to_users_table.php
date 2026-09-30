<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_login_at')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'last_login_ip')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('last_login_ip', 45)->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'password_changed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('password_changed_at')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'account_disabled_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('account_disabled_at')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'account_disabled_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('account_disabled_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('users', 'account_disabled_reason')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('account_disabled_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'account_disabled_reason',
            'account_disabled_by',
            'account_disabled_at',
            'password_changed_at',
            'last_login_ip',
            'last_login_at',
        ] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
