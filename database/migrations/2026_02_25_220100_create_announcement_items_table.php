<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('announcement_set_id')
                ->constrained('announcement_sets')
                ->cascadeOnDelete();

            $table->unsignedInteger('position')
                ->default(1)
                ->comment('Pozycja ogloszenia wewnatrz zestawu');
            $table->string('title')
                ->nullable()
                ->comment('Opcjonalny naglowek pojedynczego ogloszenia');
            $table->text('content')
                ->comment('Pelna tresc pojedynczego ogloszenia');

            $table->boolean('is_important')
                ->default(false)
                ->comment('Czy ogloszenie ma byc wyroznione');
            $table->boolean('is_active')
                ->default(true)
                ->comment('Czy ogloszenie jest aktywne i widoczne');

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

            $table->index(['announcement_set_id', 'position']);
            $table->index(['announcement_set_id', 'is_important']);
            $table->index(['announcement_set_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_items');
    }
};
