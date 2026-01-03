<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('news_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('news_post_id')->constrained('news_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->text('content');
            $table->foreignId('parent_comment_id')
                ->comment('Jeśli null, to komentarz główny, tzw. rodzic; jeśli nie, to odpowiedź na inny komentarz')
                ->nullable()
                ->constrained('news_comments')->cascadeOnDelete();

            $table->string('status')->default('visible'); // visible|hidden
            $table->timestamps();

            $table->index(['news_post_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_comments');
    }
};
