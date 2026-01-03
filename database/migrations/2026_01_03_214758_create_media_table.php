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
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('uploader_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parish_id')->nullable()->constrained('parishes')->nullOnDelete();

            $table->string('disk');                 // np. public, s3, private
            $table->string('path');                 // pełna ścieżka na dysku
            $table->string('original_name');
            $table->string('file_name');            // nazwa w storage
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->string('visibility')->default('public'); // public/private
            $table->json('meta')->nullable();

            // JEDNO przypięcie (wystarczy na start i pod “Media”)
            $table->nullableMorphs('attachable');   // attachable_type, attachable_id
            $table->string('collection')->nullable(); // np. "attachments", "cover"

            $table->timestamps();

            $table->index(['parish_id', 'disk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
