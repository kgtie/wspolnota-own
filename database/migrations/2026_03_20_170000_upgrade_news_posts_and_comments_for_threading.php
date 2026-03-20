<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_posts', function (Blueprint $table) {
            $table->boolean('comments_enabled')
                ->default(true)
                ->after('is_pinned')
                ->comment('Czy komentowanie wpisu jest wlaczone dla tego posta.');
        });

        Schema::table('news_comments', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('user_id')
                ->constrained('news_comments')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('depth')
                ->default(0)
                ->after('parent_id')
                ->comment('0 = komentarz glowny, 1 = odpowiedz, 2 = odpowiedz na odpowiedz.');
            $table->boolean('is_hidden')
                ->default(false)
                ->after('body');
            $table->timestamp('hidden_at')
                ->nullable()
                ->after('is_hidden');
            $table->foreignId('hidden_by_user_id')
                ->nullable()
                ->after('hidden_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['news_post_id', 'parent_id', 'created_at'], 'news_comments_post_parent_created_idx');
            $table->index(['news_post_id', 'depth', 'created_at'], 'news_comments_post_depth_created_idx');
            $table->index(['news_post_id', 'is_hidden', 'created_at'], 'news_comments_post_hidden_created_idx');
        });

        DB::table('news_posts')->update([
            'comments_enabled' => true,
        ]);

        DB::table('news_comments')->update([
            'depth' => 0,
            'is_hidden' => false,
            'hidden_at' => null,
            'hidden_by_user_id' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('news_comments', function (Blueprint $table) {
            $table->dropIndex('news_comments_post_parent_created_idx');
            $table->dropIndex('news_comments_post_depth_created_idx');
            $table->dropIndex('news_comments_post_hidden_created_idx');
            $table->dropConstrainedForeignId('hidden_by_user_id');
            $table->dropColumn(['hidden_at', 'is_hidden', 'depth']);
            $table->dropConstrainedForeignId('parent_id');
        });

        Schema::table('news_posts', function (Blueprint $table) {
            $table->dropColumn('comments_enabled');
        });
    }
};
