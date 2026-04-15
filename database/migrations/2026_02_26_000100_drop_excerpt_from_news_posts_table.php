<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('news_posts', 'excerpt')) {
            return;
        }

        Schema::table('news_posts', function (Blueprint $table): void {
            $table->dropColumn('excerpt');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('news_posts', 'excerpt')) {
            return;
        }

        Schema::table('news_posts', function (Blueprint $table): void {
            $table->text('excerpt')
                ->nullable()
                ->after('slug')
                ->comment('Krotki zajawkowy opis wpisu.');
        });
    }
};
