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

            // Dane podstawowe
            $table->string('name');                              // Login / Nazwa wyświetlana
            $table->string('full_name')->nullable();             // Imię i nazwisko
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Rola i status
            $table->unsignedTinyInteger('role')->default(0)
                ->comment('0: User, 1: Admin, 2: SuperAdmin');
            $table->string('status', 20)->default('active')
                ->comment('active, inactive, banned');

            // Parafia domowa (rejestracja)
            $table->foreignId('home_parish_id')
                ->nullable()
                ->constrained('parishes')
                ->nullOnDelete();

            // Weryfikacja przez proboszcza (9-cyfrowy kod)
            $table->string('verification_code', 9)->nullable()->unique();
            $table->boolean('is_user_verified')->default(false);
            $table->timestamp('user_verified_at')->nullable();
            $table->foreignId('verified_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Kto zatwierdził tego użytkownika');

            // Kontekst sesji
            $table->foreignId('current_parish_id')
                ->nullable()
                ->constrained('parishes')
                ->nullOnDelete()
                ->comment('Aktualnie przeglądana parafia (Aplikacja PWA)');

            $table->foreignId('last_managed_parish_id')
                ->nullable()
                ->constrained('parishes')
                ->nullOnDelete()
                ->comment('Ostatnio zarządzana parafia (Panel Admin)');

            // Profil
            $table->string('avatar')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });

        // Standard Laravel tables
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

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
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
