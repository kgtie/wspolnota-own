<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mass_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mass_id')
                ->constrained('masses')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('registered_at')
                ->nullable()
                ->comment('Data zapisu uczestnika na msze');
            $table->timestamps();

            $table->unique(['mass_id', 'user_id']);
            $table->index(['user_id', 'mass_id']);
            $table->index(['mass_id', 'registered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mass_user');
    }
};
