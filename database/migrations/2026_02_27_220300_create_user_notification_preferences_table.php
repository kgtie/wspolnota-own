<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('news_push')->default(true);
            $table->boolean('news_email')->default(false);
            $table->boolean('announcements_push')->default(true);
            $table->boolean('announcements_email')->default(true);
            $table->boolean('office_messages_push')->default(true);
            $table->boolean('office_messages_email')->default(true);
            $table->boolean('parish_approval_status_push')->default(true);
            $table->boolean('parish_approval_status_email')->default(true);
            $table->boolean('auth_security_push')->default(false);
            $table->boolean('auth_security_email')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
