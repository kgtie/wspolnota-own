<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_devices', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_devices', 'parish_id')) {
                $table->foreignId('parish_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('parishes')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('user_devices', 'permission_status')) {
                $table->string('permission_status', 32)
                    ->default('authorized')
                    ->after('timezone');
            }

            if (! Schema::hasColumn('user_devices', 'push_token_updated_at')) {
                $table->timestamp('push_token_updated_at')
                    ->nullable()
                    ->after('permission_status');
            }

            if (! Schema::hasColumn('user_devices', 'last_push_sent_at')) {
                $table->timestamp('last_push_sent_at')
                    ->nullable()
                    ->after('push_token_updated_at');
            }

            if (! Schema::hasColumn('user_devices', 'last_push_error_at')) {
                $table->timestamp('last_push_error_at')
                    ->nullable()
                    ->after('last_push_sent_at');
            }

            if (! Schema::hasColumn('user_devices', 'last_push_error')) {
                $table->text('last_push_error')
                    ->nullable()
                    ->after('last_push_error_at');
            }

            if (! Schema::hasColumn('user_devices', 'disabled_at')) {
                $table->timestamp('disabled_at')
                    ->nullable()
                    ->after('last_push_error');
            }
        });

        DB::table('user_devices')
            ->whereNull('push_token_updated_at')
            ->update([
                'push_token_updated_at' => now(),
                'permission_status' => DB::raw("COALESCE(permission_status, 'authorized')"),
            ]);
    }

    public function down(): void
    {
        Schema::table('user_devices', function (Blueprint $table): void {
            if (Schema::hasColumn('user_devices', 'parish_id')) {
                $table->dropConstrainedForeignId('parish_id');
            }

            foreach ([
                'permission_status',
                'push_token_updated_at',
                'last_push_sent_at',
                'last_push_error_at',
                'last_push_error',
                'disabled_at',
            ] as $column) {
                if (Schema::hasColumn('user_devices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
