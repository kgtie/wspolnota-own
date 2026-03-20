<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class NewsComment extends Model
{
    use HasFactory, SoftDeletes;

    public const MAX_DEPTH = 2;

    protected $fillable = [
        'news_post_id',
        'user_id',
        'parent_id',
        'depth',
        'body',
        'is_hidden',
        'hidden_at',
        'hidden_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'is_hidden' => 'boolean',
            'hidden_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $comment): void {
            $comment->body = trim((string) ($comment->body ?? ''));

            if ($comment->body === '') {
                throw ValidationException::withMessages([
                    'body' => 'Komentarz nie moze byc pusty.',
                ]);
            }

            if (blank($comment->parent_id)) {
                $comment->parent_id = null;
                $comment->depth = 0;

                return;
            }

            if ($comment->exists && ((int) $comment->parent_id === (int) $comment->getKey())) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Komentarz nie moze byc swoim wlasnym rodzicem.',
                ]);
            }

            $parent = static::withTrashed()->find($comment->parent_id);

            if (! $parent instanceof self) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Komentarz nadrzedny nie istnieje.',
                ]);
            }

            static::assertParentBelongsToPost((int) $comment->news_post_id, $parent);

            $comment->depth = static::resolveDepth($parent);
        });
    }

    public function newsPost(): BelongsTo
    {
        return $this->belongsTo(NewsPost::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by_user_id');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function canReceiveReplies(): bool
    {
        return $this->depth < self::MAX_DEPTH;
    }

    public function markHidden(?User $actor = null): void
    {
        $this->update([
            'is_hidden' => true,
            'hidden_at' => now(),
            'hidden_by_user_id' => $actor?->getKey(),
        ]);
    }

    public function restoreVisibility(): void
    {
        $this->update([
            'is_hidden' => false,
            'hidden_at' => null,
            'hidden_by_user_id' => null,
        ]);
    }

    public static function resolveDepth(?self $parent): int
    {
        return $parent instanceof self ? ($parent->depth + 1) : 0;
    }

    public static function assertParentBelongsToPost(int $newsPostId, self $parent): void
    {
        if ((int) $parent->news_post_id !== $newsPostId) {
            throw ValidationException::withMessages([
                'parent_id' => 'Komentarz nadrzedny musi nalezec do tego samego wpisu.',
            ]);
        }

        if (! $parent->canReceiveReplies()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Osiagnieto maksymalna glebokosc odpowiedzi dla tego komentarza.',
            ]);
        }
    }
}
