<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcement_sets', function (Blueprint $table) {
            if (! Schema::hasColumn('announcement_sets', 'summary_ai')) {
                $table->text('summary_ai')
                    ->nullable()
                    ->after('footer_notes')
                    ->comment('Podsumowanie zestawu ogloszen wygenerowane przez AI');
            }

            if (! Schema::hasColumn('announcement_sets', 'summary_generated_at')) {
                $table->timestamp('summary_generated_at')
                    ->nullable()
                    ->after('summary_ai')
                    ->comment('Data wygenerowania podsumowania AI');
            }

            if (! Schema::hasColumn('announcement_sets', 'summary_model')) {
                $table->string('summary_model', 120)
                    ->nullable()
                    ->after('summary_generated_at')
                    ->comment('Model AI uzyty do wygenerowania streszczenia');
            }

            if (! Schema::hasColumn('announcement_sets', 'notifications_sent_at')) {
                $table->timestamp('notifications_sent_at')
                    ->nullable()
                    ->after('summary_model')
                    ->comment('Data wysylki emaili informujacych parafian o aktualnych ogloszeniach');
            }

            if (! Schema::hasColumn('announcement_sets', 'notifications_recipients_count')) {
                $table->unsignedInteger('notifications_recipients_count')
                    ->nullable()
                    ->after('notifications_sent_at')
                    ->comment('Liczba odbiorcow emaila o ogloszeniach');
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcement_sets', function (Blueprint $table) {
            $columns = [
                'summary_generated_at',
                'summary_model',
                'notifications_sent_at',
                'notifications_recipients_count',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('announcement_sets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
