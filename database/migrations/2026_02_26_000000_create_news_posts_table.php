<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parish_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title')
                ->comment('Tytul aktualnosci publikowanej przez administratora parafii.');
            $table->string('slug')
                ->comment('Slug URL unikalny w ramach parafii.');
            $table->longText('content')
                ->comment('Pelna tresc wpisu (HTML z edytora WYSIWYG).');

            $table->string('status', 30)
                ->default('draft')
                ->comment('draft/scheduled/published/archived');
            $table->timestamp('published_at')
                ->nullable()
                ->comment('Rzeczywisty moment publikacji.');
            $table->timestamp('scheduled_for')
                ->nullable()
                ->comment('Zaplanowany moment automatycznej publikacji.');
            $table->boolean('is_pinned')
                ->default(false)
                ->comment('Czy wpis powinien byc przypiety na gorze listy.');

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['parish_id', 'slug']);
            $table->index(['parish_id', 'status']);
            $table->index(['parish_id', 'published_at']);
            $table->index(['parish_id', 'scheduled_for']);
            $table->index(['parish_id', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_posts');
    }
};
