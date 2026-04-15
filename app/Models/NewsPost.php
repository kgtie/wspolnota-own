<?php

namespace App\Models;

use App\Events\NewsPublished;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class NewsPost extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    public const STATUS_OPTIONS = [
        'draft' => 'Szkic',
        'scheduled' => 'Zaplanowany',
        'published' => 'Opublikowany',
        'archived' => 'Archiwalny',
    ];

    protected $fillable = [
        'parish_id',
        'title',
        'slug',
        'content',
        'status',
        'published_at',
        'push_notification_sent_at',
        'email_notification_sent_at',
        'scheduled_for',
        'is_pinned',
        'comments_enabled',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'push_notification_sent_at' => 'datetime',
            'email_notification_sent_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'is_pinned' => 'boolean',
            'comments_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $post): void {
            $post->title = self::normalizeTextColumn($post->title);
            $post->content = self::normalizeLongTextColumn($post->content);

            $hasDraftPlaceholderSlug = str_starts_with((string) $post->slug, 'draft-');

            if (blank(trim((string) $post->title))) {
                if (blank($post->slug)) {
                    $post->slug = 'draft-'.Str::lower(Str::random(12));
                }

                return;
            }

            if (blank($post->slug) || $hasDraftPlaceholderSlug || ($post->isDirty('title') && ($post->status === 'draft'))) {
                $post->slug = $post->generateUniqueSlug();
            }
        });

        static::saved(function (self $post): void {
            if ($post->status !== 'published') {
                return;
            }

            $becamePublished = $post->wasRecentlyCreated || $post->wasChanged('status');

            if ($becamePublished) {
                event(new NewsPublished($post->fresh()));
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'parish_id',
                'title',
                'slug',
                'content',
                'status',
                'published_at',
                'scheduled_for',
                'is_pinned',
                'comments_enabled',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Aktualnosc zostala {$eventName}");
    }

    public static function getStatusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')
            ->useDisk('news')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('gallery')
            ->useDisk('news')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('attachments')
            ->useDisk('news')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);

        $this->addMediaCollection('content_images')
            ->useDisk('news')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(240)
            ->height(160)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(1024)
            ->height(640)
            ->nonQueued();
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NewsComment::class)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function rootComments(): HasMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    public function visibleComments(): HasMany
    {
        return $this->comments()->where('is_hidden', false);
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', 'published')
            ->where(function ($inner) {
                $inner
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function isVisibleOnFrontend(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if (! $this->published_at) {
            return true;
        }

        return $this->published_at->lte(now());
    }

    public function getDisplayTitle(): string
    {
        $title = trim((string) $this->title);

        return $title !== '' ? $title : 'Nowy szkic bez tytulu';
    }

    public function allowsComments(): bool
    {
        return (bool) $this->comments_enabled
            && (bool) $this->parish?->getSetting('news_comments_enabled', true);
    }

    public function requiresVerifiedCommenter(): bool
    {
        return (bool) $this->parish?->getSetting('news_comments_require_verification', true);
    }

    public static function normalizeTextColumn(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    public static function normalizeLongTextColumn(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    public function generateUniqueSlug(): string
    {
        $baseSlug = Str::slug((string) $this->title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'aktualnosc';

        $slug = $baseSlug;
        $suffix = 2;

        while (
            static::query()
                ->where('parish_id', $this->parish_id)
                ->where('slug', $slug)
                ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
