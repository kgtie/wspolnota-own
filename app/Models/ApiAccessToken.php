<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiAccessToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'refresh_token_id',
        'token_hash',
        'device_id',
        'expires_at',
        'last_used_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refreshToken(): BelongsTo
    {
        return $this->belongsTo(ApiRefreshToken::class, 'refresh_token_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')->where('expires_at', '>', now());
    }
}
