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
        Schema::create('parishes', function (Blueprint $table) {
            $table->id();
            
            // Podstawowe dane
            $table->string('name');                          // Pełna nazwa: "Parafia p.w. św. Stanisława..."
            $table->string('short_name');                    // Krótka: "Parafia Wiskitki"
            $table->string('slug')->unique();                // URL: "wiskitki"
            
            // Dane kontaktowe
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            
            // Adres
            $table->string('street')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city');
            $table->string('diocese')->nullable();           // Diecezja
            $table->string('decanate')->nullable();          // Dekanat
            
            // Media
            $table->string('avatar')->nullable();            // Logo/zdjęcie parafii
            $table->string('cover_image')->nullable();       // Zdjęcie w tle
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Ustawienia (JSON)
            $table->json('settings')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parishes');
    }
};
