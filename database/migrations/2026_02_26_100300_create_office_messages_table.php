<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('office_conversation_id')
                ->constrained('office_conversations')
                ->cascadeOnDelete();

            $table->foreignId('sender_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->longText('body')->nullable();
            $table->boolean('has_attachments')->default(false);
            $table->timestamp('read_by_parishioner_at')->nullable();
            $table->timestamp('read_by_priest_at')->nullable();
            $table->timestamps();

            $table->index(['office_conversation_id', 'created_at'], 'office_msg_conversation_created_idx');
            $table->index(['sender_user_id', 'created_at'], 'office_msg_sender_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_messages');
    }
};
