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
    Schema::create('mailing_mails', function (Blueprint $table) {
        $table->id();
        // Powiązanie z konkretną listą (np. ID listy "Oczekujący")
        $table->foreignId('mailing_list_id')->constrained()->cascadeOnDelete();

        $table->string('email');

        // Double Opt-In
        $table->string('confirmation_token')->nullable(); 
        $table->timestamp('confirmed_at')->nullable();

        // Soft Deletes (Wypisanie się, ale rekord zostaje)
        $table->string('unsubscribe_token')->nullable();
        $table->softDeletes();
        $table->timestamps();

        // Email musi być unikalny w ramach jednej listy, ALE uwzględniając soft deletes
        // (technicznie unikalność na poziomie bazy może być trudna z softDeletes, 
        // obsłużymy to w logice aplikacji)
        $table->index(['email', 'mailing_list_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailing_mails');
    }
};
