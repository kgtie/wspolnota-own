<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migracja dla tabel announcement_sets i announcements
 * 
 * Zestaw ogłoszeń (announcement_sets) to "kontener" na pojedyncze ogłoszenia
 * obowiązujące w danym tygodniu, np. "Ogłoszenia na XXI niedzielę zwykłą".
 * 
 * Pojedyncze ogłoszenia (announcements) to poszczególne punkty w zestawie,
 * np. "Dziś zbiórka do puszek na ofiary przemocy w rodzinach".
 */
return new class extends Migration
{
    public function up(): void
    {
        // Zestawy ogłoszeń (pakiety)
        Schema::create('announcement_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parish_id')->constrained('parishes')->cascadeOnDelete();
            
            // Tytuł zestawu
            $table->string('title')->comment('Nazwa zestawu, np. "Ogłoszenia na XXI niedzielę zwykłą"');
            
            // Okres obowiązywania
            $table->date('valid_from')->comment('Data początku obowiązywania');
            $table->date('valid_until')->comment('Data końca obowiązywania');
            
            // Streszczenie AI (generowane przez Gemini w przyszłości)
            $table->text('ai_summary')->nullable()->comment('Streszczenie wygenerowane przez AI');
            $table->timestamp('ai_summary_generated_at')->nullable();
            
            // Status publikacji
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            
            // Kto utworzył/opublikował
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Indeksy dla szybkiego wyszukiwania
            $table->index(['parish_id', 'valid_from', 'valid_until']);
            $table->index('status');
        });

        // Pojedyncze ogłoszenia w zestawie
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_set_id')->constrained('announcement_sets')->cascadeOnDelete();
            
            // Treść ogłoszenia
            $table->text('content')->comment('Treść pojedynczego ogłoszenia');
            
            // Kolejność wyświetlania (drag & drop)
            $table->unsignedInteger('sort_order')->default(0);
            
            // Opcjonalne wyróżnienie (ważne ogłoszenie)
            $table->boolean('is_highlighted')->default(false)->comment('Czy ogłoszenie jest wyróżnione');
            
            $table->timestamps();
            
            $table->index(['announcement_set_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('announcement_sets');
    }
};
