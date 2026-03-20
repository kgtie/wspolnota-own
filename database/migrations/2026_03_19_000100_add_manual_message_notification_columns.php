<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_notification_preferences')) {
            return;
        }

        Schema::table('user_notification_preferences', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_notification_preferences', 'manual_messages_push')) {
                $table->boolean('manual_messages_push')
                    ->default(false)
                    ->after('auth_security_email');
            }

            if (! Schema::hasColumn('user_notification_preferences', 'manual_messages_email')) {
                $table->boolean('manual_messages_email')
                    ->default(true)
                    ->after('manual_messages_push');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_notification_preferences')) {
            return;
        }

        Schema::table('user_notification_preferences', function (Blueprint $table): void {
            foreach (['manual_messages_push', 'manual_messages_email'] as $column) {
                if (Schema::hasColumn('user_notification_preferences', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
