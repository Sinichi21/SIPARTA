<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('incoming_letters')) {
            Schema::table('incoming_letters', function (Blueprint $table) {
                if (Schema::hasColumn('incoming_letters', 'letter_template_id')) {
                    $table->dropConstrainedForeignId('letter_template_id');
                }

                if (Schema::hasColumn('incoming_letters', 'placeholder_data')) {
                    $table->dropColumn('placeholder_data');
                }
            });
        }

        if (
            Schema::hasTable('outgoing_letters')
            && ! Schema::hasColumn('outgoing_letters', 'placeholder_data')
        ) {
            Schema::table('outgoing_letters', function (Blueprint $table) {
                $table->json('placeholder_data')
                    ->nullable()
                    ->after('content_html');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('outgoing_letters')
            && Schema::hasColumn('outgoing_letters', 'placeholder_data')
        ) {
            Schema::table('outgoing_letters', function (Blueprint $table) {
                $table->dropColumn('placeholder_data');
            });
        }

        if (
            Schema::hasTable('incoming_letters')
            && ! Schema::hasColumn('incoming_letters', 'letter_template_id')
        ) {
            Schema::table('incoming_letters', function (Blueprint $table) {
                $table->foreignId('letter_template_id')
                    ->nullable()
                    ->constrained('letter_templates')
                    ->nullOnDelete();

                $table->json('placeholder_data')->nullable();
            });
        }
    }
};
