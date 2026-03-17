<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UserDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parish_id',
        'provider',
        'platform',
        'push_token',
        'device_id',
        'device_name',
        'app_version',
        'locale',
        'timezone',
        'permission_status',
        'push_token_updated_at',
        'last_seen_at',
        'last_push_sent_at',
        'last_push_error_at',
        'last_push_error',
        'disabled_at',
    ];

    protected function casts(): array
    {
        return [
            'push_token_updated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_push_sent_at' => 'datetime',
            'last_push_error_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('disabled_at');
    }

    public function scopePushable(Builder $query): Builder
    {
        return $query
            ->active()
            ->where('provider', 'fcm')
            ->whereIn('permission_status', ['authorized', 'provisional'])
            ->whereNotNull('push_token');
    }

    public function scopeDeadToken(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('last_push_error', 'like', '%UNREGISTERED%')
                ->orWhere('last_push_error', 'like', '%INVALID_ARGUMENT%');
        });
    }

    public function markPushSent(): void
    {
        $this->forceFill([
            'last_push_sent_at' => now(),
            'last_push_error_at' => null,
            'last_push_error' => null,
        ])->saveQuietly();
    }

    public function markPushFailed(string $error, bool $disable = false): void
    {
        $this->forceFill([
            'last_push_error_at' => now(),
            'last_push_error' => $error,
            'disabled_at' => $disable ? now() : $this->disabled_at,
        ])->saveQuietly();
    }
}
