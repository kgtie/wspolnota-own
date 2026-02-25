<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_sets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parish_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title')
                ->comment('Nazwa zestawu ogloszen, np. XII tydzien zwykly');
            $table->string('week_label')
                ->nullable()
                ->comment('Opis tygodnia liturgicznego lub kalendarzowego');
            $table->date('effective_from')
                ->comment('Data od ktorej zestaw obowiazuje');
            $table->date('effective_to')
                ->nullable()
                ->comment('Data do ktorej zestaw obowiazuje');

            $table->string('status', 30)
                ->default('draft')
                ->comment('draft/published/archived');
            $table->timestamp('published_at')
                ->nullable()
                ->comment('Data publikacji zestawu');

            $table->text('lead')
                ->nullable()
                ->comment('Wstep do zestawu ogloszen');
            $table->text('footer_notes')
                ->nullable()
                ->comment('Notatki koncowe, np. podpis proboszcza');
            $table->text('summary_ai')
                ->nullable()
                ->comment('Podsumowanie zestawu ogloszen wygenerowane przez AI');

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

            $table->index(['parish_id', 'effective_from']);
            $table->index(['parish_id', 'status']);
            $table->index(['parish_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_sets');
    }
};
