<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mass_user')) {
            Schema::table('mass_user', function (Blueprint $table): void {
                if (! Schema::hasColumn('mass_user', 'reminder_push_24h_sent_at')) {
                    $table->timestamp('reminder_push_24h_sent_at')
                        ->nullable()
                        ->after('registered_at');
                }

                if (! Schema::hasColumn('mass_user', 'reminder_push_8h_sent_at')) {
                    $table->timestamp('reminder_push_8h_sent_at')
                        ->nullable()
                        ->after('reminder_push_24h_sent_at');
                }

                if (! Schema::hasColumn('mass_user', 'reminder_push_1h_sent_at')) {
                    $table->timestamp('reminder_push_1h_sent_at')
                        ->nullable()
                        ->after('reminder_push_8h_sent_at');
                }

                if (! Schema::hasColumn('mass_user', 'reminder_email_sent_at')) {
                    $table->timestamp('reminder_email_sent_at')
                        ->nullable()
                        ->after('reminder_push_1h_sent_at');
                }
            });
        }

        if (Schema::hasTable('user_notification_preferences')) {
            Schema::table('user_notification_preferences', function (Blueprint $table): void {
                if (! Schema::hasColumn('user_notification_preferences', 'mass_reminders_push')) {
                    $table->boolean('mass_reminders_push')
                        ->default(true)
                        ->after('announcements_email');
                }

                if (! Schema::hasColumn('user_notification_preferences', 'mass_reminders_email')) {
                    $table->boolean('mass_reminders_email')
                        ->default(true)
                        ->after('mass_reminders_push');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mass_user')) {
            Schema::table('mass_user', function (Blueprint $table): void {
                foreach ([
                    'reminder_push_24h_sent_at',
                    'reminder_push_8h_sent_at',
                    'reminder_push_1h_sent_at',
                    'reminder_email_sent_at',
                ] as $column) {
                    if (Schema::hasColumn('mass_user', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('user_notification_preferences')) {
            Schema::table('user_notification_preferences', function (Blueprint $table): void {
                foreach (['mass_reminders_push', 'mass_reminders_email'] as $column) {
                    if (Schema::hasColumn('user_notification_preferences', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
