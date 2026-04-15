<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_device_id')->nullable()->constrained('user_devices')->nullOnDelete();
            $table->string('notification_id', 64)->nullable()->index();
            $table->string('provider', 16)->default('fcm');
            $table->string('platform', 16)->nullable()->index();
            $table->string('type', 120)->nullable()->index();
            $table->string('status', 32)->index();
            $table->string('collapse_key', 120)->nullable();
            $table->string('message_id', 255)->nullable()->index();
            $table->string('error_code', 120)->nullable()->index();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_deliveries');
    }
};
