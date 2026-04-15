<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parish_user', function (Blueprint $table) {
            $table->id(); // Własne ID ułatwia zarządzanie w Filament

            $table->foreignId('parish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamp('assigned_at')->nullable();
            $table->string('note')->nullable();

            // Unikalność pary
            $table->unique(['parish_id', 'user_id']);

            // Indeksy dla szybkiego sprawdzania dostępu
            $table->index(['user_id', 'is_active']);
            $table->index(['parish_id', 'is_active']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parish_user');
    }
};
