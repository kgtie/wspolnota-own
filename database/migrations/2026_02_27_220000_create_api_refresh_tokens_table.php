<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('family_id')->index();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('replaced_by_id')->nullable()->constrained('api_refresh_tokens')->nullOnDelete();
            $table->string('device_id', 128)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'family_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_refresh_tokens');
    }
};
