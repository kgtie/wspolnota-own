<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('masses', function (Blueprint $table) {
            $table->id();
            
            // Powiązanie z parafią
            $table->foreignId('parish_id')->constrained('parishes')->cascadeOnDelete();
            
            // Czas i Miejsce
            $table->dateTime('start_time'); // Data i godzina
            $table->string('location')->default('Kościół główny'); // Np. Kaplica, Kościół
            
            // Szczegóły liturgiczne
            $table->text('intention'); // Intencja mszalna
            $table->string('type')->default('z dnia'); // np. Z dnia, Pogrzebowa, Ślubna
            $table->string('rite')->default('rzymski'); // np. Rzymski, Trydencki
            
            // Obsada i Finanse
            $table->string('celebrant')->nullable(); // Imię i nazwisko księdza (opcjonalne)
            $table->decimal('stipend', 10, 2)->nullable(); // Ofiara (widoczne tylko dla admina)
            
            $table->timestamps();
        });

        // Tabela pivot do zapisów użytkowników na mszę
        Schema::create('mass_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mass_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            // Użytkownik może zapisać się na daną mszę tylko raz
            $table->unique(['mass_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mass_user');
        Schema::dropIfExists('masses');
    }
};