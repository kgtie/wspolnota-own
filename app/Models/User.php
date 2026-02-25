<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser, HasTenants, HasDefaultTenant, HasMedia
{
    use HasFactory, Notifiable, SoftDeletes, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password',
        'avatar',
        'role',
        'status',
        'home_parish_id',
        'current_parish_id',
        'last_managed_parish_id',
        'verification_code',
        'is_user_verified',
        'user_verified_at',
        'verified_by_user_id',
        'last_login_at',
        'deleted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'user_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_user_verified' => 'boolean',
            'role' => 'integer',
        ];
    }

    // =========================================
    // SPATIE MEDIA LIBRARY
    // =========================================

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->sharpen(10)
            ->nonQueued();
    }

    // =========================================
    // SPATIE ACTIVITY LOG
    // =========================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'full_name', 'email', 'role', 'status',
                'home_parish_id', 'is_user_verified', 'email_verified_at',
                'user_verified_at', 'verified_by_user_id', 'verification_code',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Użytkownik został {$eventName}");
    }

    // =========================================
    // RELACJE
    // =========================================

    public function homeParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'home_parish_id');
    }

    public function currentParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'current_parish_id');
    }

    public function lastManagedParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'last_managed_parish_id');
    }

    public function managedParishes(): BelongsToMany
    {
        return $this->belongsToMany(Parish::class, 'parish_user')
            ->withPivot(['is_active', 'assigned_at', 'note'])
            ->withTimestamps();
    }

    public function registeredMasses(): BelongsToMany
    {
        return $this->belongsToMany(Mass::class, 'mass_user')
            ->withPivot(['registered_at'])
            ->withTimestamps();
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    // =========================================
    // FILAMENT: FilamentUser
    // =========================================

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isAdmin() && $this->status === 'active',
            'superadmin' => $this->isSuperAdmin() && $this->status === 'active',
            default => false,
        };
    }

    // =========================================
    // FILAMENT: HasTenants (multi-tenancy)
    // =========================================

    public function getTenants(Panel $panel): Collection
    {
        return $this->managedParishes()
            ->wherePivot('is_active', true)
            ->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->managedParishes()
            ->wherePivot('is_active', true)
            ->whereKey($tenant)
            ->exists();
    }

    // =========================================
    // FILAMENT: HasDefaultTenant
    // =========================================

    public function getDefaultTenant(Panel $panel): ?Model
    {
        if ($this->last_managed_parish_id) {
            $lastManaged = $this->managedParishes()
                ->wherePivot('is_active', true)
                ->whereKey($this->last_managed_parish_id)
                ->first();

            if ($lastManaged) {
                return $lastManaged;
            }
        }

        return $this->managedParishes()
            ->wherePivot('is_active', true)
            ->first();
    }

    // =========================================
    // HELPERY RÓL
    // =========================================

    public function isSuperAdmin(): bool
    {
        return $this->role === 2;
    }

    public function isAdmin(): bool
    {
        return $this->role >= 1;
    }

    public function isRegularUser(): bool
    {
        return $this->role === 0;
    }

    // =========================================
    // WERYFIKACJA (9-cyfrowy kod)
    // =========================================

    public function generateVerificationCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (static::where('verification_code', $code)->exists());

        $this->update(['verification_code' => $code]);

        return $code;
    }

    public function verify(?User $verifiedBy = null): void
    {
        $this->update([
            'is_user_verified' => true,
            'user_verified_at' => now(),
            'verified_by_user_id' => $verifiedBy?->id,
        ]);
    }

    // =========================================
    // SCOPE'Y
    // =========================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfParish($query, int $parishId)
    {
        return $query->where('home_parish_id', $parishId);
    }

    public function scopePendingVerification($query)
    {
        return $query->where('is_user_verified', false)
            ->whereNotNull('email_verified_at');
    }

    // =========================================
    // HELPERY MEDIÓW
    // =========================================

    /**
     * URL avatara (z Media Library lub fallback)
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb')
            ?: asset('images/default-user-avatar.png');
    }
}
