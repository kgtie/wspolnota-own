<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Model Parish - Parafie (Klienci usługi)
 *
 * Parafia jest głównym tenantem w systemie multi-tenancy Filament.
 * Administratorzy zarządzają przypisanymi parafiami (many-to-many przez parish_user),
 * a Użytkownicy rejestrują się do konkretnej parafii (home_parish_id).
 */
class Parish extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'name',
        'short_name',
        'slug',
        'email',
        'phone',
        'website',
        'street',
        'postal_code',
        'city',
        'diocese',
        'decanate',
        'is_active',
        'activated_at',
        'expiration_date',
        'subscription_fee',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'activated_at' => 'date',
            'expiration_date' => 'date',
            'subscription_fee' => 'decimal:2',
        ];
    }

    /**
     * Slug w URL zamiast ID
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // =========================================
    // SPATIE MEDIA LIBRARY
    // =========================================

    /**
     * Definiuje kolekcje mediów dla parafii.
     * - avatar: logo/zdjęcie parafii (1 plik)
     * - cover: zdjęcie w tle (1 plik)
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->useDisk('profiles')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('cover')
            ->useDisk('profiles')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Definiuje konwersje (thumbnails) dla mediów.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->nonQueued(); // Na razie bez kolejki

        $this->addMediaConversion('preview')
            ->width(600)
            ->height(400)
            ->nonQueued();
    }

    // =========================================
    // SPATIE ACTIVITY LOG
    // =========================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'short_name', 'slug', 'email', 'phone',
                'website', 'street', 'postal_code', 'city',
                'diocese', 'decanate', 'is_active', 'settings',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Parafia została {$eventName}");
    }

    // =========================================
    // SETTINGS HELPER
    // =========================================

    /**
     * Pobiera ustawienie parafii z mergowanymi defaults
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return \App\Settings\ParishSettings::get($this->settings, $key, $default);
    }

    /**
     * Ustawia pojedynczy klucz w settings JSON
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->update(['settings' => $settings]);
    }

    // =========================================
    // RELACJE
    // =========================================

    /**
     * Administratorzy przypisani do parafii (przez parish_user pivot).
     * Filament używa tej relacji do multi-tenancy.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parish_user')
            ->withPivot(['is_active', 'assigned_at', 'note'])
            ->withTimestamps();
    }

    /**
     * Aktywni administratorzy przypisani do parafii przez pivot.
     * Pivot jest źródłem prawdy dla przypisań administratorów parafii.
     */
    public function admins(): BelongsToMany
    {
        return $this->users()
            ->wherePivot('is_active', true);
    }

    /**
     * Parafianie — Użytkownicy zarejestrowani do tej parafii
     */
    public function parishioners(): HasMany
    {
        return $this->hasMany(User::class, 'home_parish_id');
    }

    /**
     * Parafianie zatwierdzeni przez proboszcza
     */
    public function verifiedParishioners(): HasMany
    {
        return $this->parishioners()->where('is_user_verified', true);
    }

    /**
     * Msze swiete i intencje mszalne zarejestrowane dla parafii
     */
    public function masses(): HasMany
    {
        return $this->hasMany(Mass::class);
    }

    /**
     * Zestawy ogloszen parafialnych.
     */
    public function announcementSets(): HasMany
    {
        return $this->hasMany(AnnouncementSet::class);
    }

    // =========================================
    // HELPERY
    // =========================================

    public function getParishionersCountAttribute(): int
    {
        return $this->parishioners()->count();
    }

    public function getPendingVerificationsCountAttribute(): int
    {
        return $this->parishioners()
            ->where('is_user_verified', false)
            ->whereNotNull('email_verified_at')
            ->count();
    }

    /**
     * Zwraca URL avatara (z Media Library lub fallback)
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb')
            ?: asset('images/default-parish-avatar.png');
    }

    /**
     * Zwraca URL cover image
     */
    public function getCoverUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('cover', 'preview')
            ?: asset('images/default-parish-cover.png');
    }
}
