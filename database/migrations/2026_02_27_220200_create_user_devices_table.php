<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 10);
            $table->string('platform', 10);
            $table->string('push_token', 4096);
            $table->string('device_id', 128);
            $table->string('device_name', 120)->nullable();
            $table->string('app_version', 30);
            $table->string('locale', 16)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
            $table->index(['provider', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
