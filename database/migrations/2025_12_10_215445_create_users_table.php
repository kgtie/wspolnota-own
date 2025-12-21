<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Login / Nazwa wyświetlana
            $table->string('full_name')->nullable(); // Imię i nazwisko
            $table->string('email')->unique(); // Email użytkownika
            $table->timestamp('email_verified_at')->nullable(); // Weryfikacja email
            $table->string('password'); // Hasło (zahashowane)

            $table->unsignedTinyInteger('role')->default(0); // ROLA: 0: User, 1: Admin, 2: SuperAdmin

            // Użytkownik należy do jednej parafii "domowej"
            $table->foreignId('home_parish_id')
                ->nullable()
                ->constrained('parishes')
                ->nullOnDelete();

            $table->string('verification_code', 9)->nullable()->unique(); // Kod 9 cyfr do okazania proboszczowi
            $table->boolean('is_user_verified')->default(false); // Czy proboszcz zatwierdził użytkownika?
            $table->timestamp('user_verified_at')->nullable(); // Kiedy proboszcz zatwierdził użytkownika?

            // KONTEKST SESJI (Dla Admina i Usera)
            // Jaką parafię aktualnie przegląda/zarządza (może być inna niż domowa)
            $table->foreignId('current_parish_id')
                ->nullable()
                ->constrained('parishes')
                ->nullOnDelete();

            // Dodatki profilowe
            $table->string('avatar')->nullable();
            
            $table->softDeletes(); // Soft Delete
            $table->rememberToken();
            $table->timestamps();
        });

        // Tabela resetowania haseł (standard Laravel)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Tabela sesji (standard Laravel)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};