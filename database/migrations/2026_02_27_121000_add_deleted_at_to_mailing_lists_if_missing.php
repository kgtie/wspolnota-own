<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mailing_lists', 'deleted_at')) {
            Schema::table('mailing_lists', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mailing_lists', 'deleted_at')) {
            Schema::table('mailing_lists', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
