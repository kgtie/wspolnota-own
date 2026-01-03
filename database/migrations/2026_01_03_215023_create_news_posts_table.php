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
        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parish_id')->constrained('parishes')->cascadeOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->string('slug'); // unikalny w obrębie parafii
            $table->text('excerpt')->nullable();
            $table->longText('content');

            $table->string('status')->default('draft'); // draft|pending|published
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['parish_id', 'slug']);
            $table->index(['parish_id', 'status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_posts');
    }
};
