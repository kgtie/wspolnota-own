<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('news_posts')) {
            Schema::table('news_posts', function (Blueprint $table): void {
                if (! Schema::hasColumn('news_posts', 'push_notification_sent_at')) {
                    $table->timestamp('push_notification_sent_at')
                        ->nullable()
                        ->after('published_at');
                }

                if (! Schema::hasColumn('news_posts', 'email_notification_sent_at')) {
                    $table->timestamp('email_notification_sent_at')
                        ->nullable()
                        ->after('push_notification_sent_at');
                }
            });
        }

        if (Schema::hasTable('announcement_sets')) {
            Schema::table('announcement_sets', function (Blueprint $table): void {
                if (! Schema::hasColumn('announcement_sets', 'push_notification_sent_at')) {
                    $table->timestamp('push_notification_sent_at')
                        ->nullable()
                        ->after('published_at');
                }

                if (! Schema::hasColumn('announcement_sets', 'email_notification_sent_at')) {
                    $table->timestamp('email_notification_sent_at')
                        ->nullable()
                        ->after('push_notification_sent_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('news_posts')) {
            Schema::table('news_posts', function (Blueprint $table): void {
                foreach (['push_notification_sent_at', 'email_notification_sent_at'] as $column) {
                    if (Schema::hasColumn('news_posts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('announcement_sets')) {
            Schema::table('announcement_sets', function (Blueprint $table): void {
                foreach (['push_notification_sent_at', 'email_notification_sent_at'] as $column) {
                    if (Schema::hasColumn('announcement_sets', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
