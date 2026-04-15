<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('channel')->default('email');
            $table->boolean('is_template')->default(false);
            $table->string('status')->default('draft');
            $table->foreignId('parish_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_line')->nullable();
            $table->string('preheader')->nullable();
            $table->json('builder_payload');
            $table->unsignedInteger('recipients_total')->default(0);
            $table->unsignedInteger('queued_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('last_test_sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_campaigns');
    }
};
