<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class OfficeConversation extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'uuid',
        'parish_id',
        'parishioner_user_id',
        'priest_user_id',
        'status',
        'last_message_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $conversation): void {
            if (blank($conversation->uuid)) {
                $conversation->uuid = (string) Str::uuid();
            }

            if (blank($conversation->status)) {
                $conversation->status = self::STATUS_OPEN;
            }
        });
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Otwarta',
            self::STATUS_CLOSED => 'Zamknieta',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function parishioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parishioner_user_id')->withTrashed();
    }

    public function priest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'priest_user_id')->withTrashed();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OfficeMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(OfficeMessage::class)->latestOfMany();
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function close(): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => self::STATUS_OPEN,
            'closed_at' => null,
        ]);
    }
}
