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
            $table->string('phone', 20)->nullable();
            $table->string('website')->nullable();           // Dotychczasowa strona www parafii

            // Adres
            $table->string('street')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city');
            $table->string('diocese')->nullable();            // Diecezja
            $table->string('decanate')->nullable();           // Dekanat

            // Media (ścieżki na dysku profiles)
            $table->string('avatar')->nullable();             // Logo/zdjęcie parafii
            $table->string('cover_image')->nullable();        // Zdjęcie w tle

            // Status i subskrypcja
            $table->boolean('is_active')->default(true);
            $table->date('activated_at')->nullable()
                ->comment('Data (ostatniej) aktywacji parafii w systemie');
            $table->date('expiration_date')->nullable()
                ->comment('Data wygaśnięcia działalności parafii w systemie');
            $table->decimal('subscription_fee', 8, 2)->default(0.00)
                ->comment('Opłata subskrypcyjna parafii');

            // Ustawienia (JSON) - kolory, preferencje powiadomień itp.
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
