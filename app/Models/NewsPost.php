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
        'scheduled_for',
        'is_pinned',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'is_pinned' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $post): void {
            if (blank($post->slug)) {
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
        return $this->hasMany(NewsComment::class)->latest('id');
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
