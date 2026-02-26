<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_id')
                ->constrained('parishes')
                ->cascadeOnDelete();

            $table->foreignId('parishioner_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('priest_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('status', 20)->default('open')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['parish_id', 'priest_user_id', 'status', 'last_message_at'], 'office_conv_parish_priest_status_last_idx');
            $table->index(['parishioner_user_id', 'status', 'last_message_at'], 'office_conv_user_status_last_idx');
            $table->index(['parish_id', 'parishioner_user_id'], 'office_conv_parish_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_conversations');
    }
};
