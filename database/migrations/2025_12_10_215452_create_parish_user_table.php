<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela Pivot do relacji Many-to-Many
        // Głównie dla Administratorów, którzy mogą zarządzać wieloma parafiami [cite: 155]
        Schema::create('parish_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parish_id')->constrained()->cascadeOnDelete();
                        
            $table->primary(['user_id', 'parish_id']); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parish_user');
    }
};