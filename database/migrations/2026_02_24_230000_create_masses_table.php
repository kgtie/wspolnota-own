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

            $table->foreignId('parish_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('intention_title')
                ->comment('Glowna intencja mszalna');
            $table->text('intention_details')
                ->nullable()
                ->comment('Dodatkowy opis intencji');

            $table->dateTime('celebration_at')
                ->comment('Data i godzina odprawienia mszy');

            $table->decimal('stipendium_amount', 8, 2)
                ->nullable()
                ->comment('Stypendium za msze; opcjonalne');
            $table->dateTime('stipendium_paid_at')
                ->nullable()
                ->comment('Data przyjecia stypendium');

            $table->string('mass_kind', 50)
                ->default('weekday')
                ->comment('Rodzaj mszy (liturgiczny)');
            $table->string('mass_type', 50)
                ->default('individual')
                ->comment('Typ intencji mszalnej');
            $table->string('status', 30)
                ->default('scheduled')
                ->comment('scheduled/completed/cancelled');

            $table->string('celebrant_name')
                ->nullable()
                ->comment('Imie i nazwisko ksiedza celebrujacego');
            $table->string('location')
                ->nullable()
                ->comment('Miejsce odprawienia');
            $table->text('notes')
                ->nullable()
                ->comment('Notatki kancelaryjne');

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

            $table->index(['parish_id', 'celebration_at']);
            $table->index(['parish_id', 'status']);
            $table->index(['parish_id', 'mass_kind']);
            $table->index(['parish_id', 'mass_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masses');
    }
};
