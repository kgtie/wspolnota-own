<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('refresh_token_id')->nullable()->constrained('api_refresh_tokens')->nullOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('device_id', 128)->nullable()->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_access_tokens');
    }
};
