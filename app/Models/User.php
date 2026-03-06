<?php

namespace App\Models;

use App\Events\ParishApprovalStatusChanged;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasMedia, HasTenants, MustVerifyEmail
{
    use HasFactory, InteractsWithMedia, LogsActivity, Notifiable, SoftDeletes;

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

    protected static function booted(): void
    {
        static::updated(function (self $user): void {
            if ($user->wasChanged('is_user_verified')) {
                event(new ParishApprovalStatusChanged($user->fresh(), (bool) $user->is_user_verified));
            }
        });
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

    /**
     * Alias relacji wymagany przez domyslne mechanizmy Filament w relation manager.
     */
    public function masses(): BelongsToMany
    {
        return $this->registeredMasses();
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function officeConversationsAsParishioner(): HasMany
    {
        return $this->hasMany(OfficeConversation::class, 'parishioner_user_id');
    }

    public function officeConversationsAsPriest(): HasMany
    {
        return $this->hasMany(OfficeConversation::class, 'priest_user_id');
    }

    public function officeMessages(): HasMany
    {
        return $this->hasMany(OfficeMessage::class, 'sender_user_id');
    }

    public function apiAccessTokens(): HasMany
    {
        return $this->hasMany(ApiAccessToken::class);
    }

    public function apiRefreshTokens(): HasMany
    {
        return $this->hasMany(ApiRefreshToken::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(UserNotificationPreference::class);
    }

    public function newsComments(): HasMany
    {
        return $this->hasMany(NewsComment::class);
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

    public function resetParishApproval(): void
    {
        $this->forceFill([
            'is_user_verified' => false,
            'user_verified_at' => null,
            'verified_by_user_id' => null,
            'verification_code' => null,
        ])->save();
    }

    public function canAccessOffice(): bool
    {
        return $this->hasVerifiedEmail() && (bool) $this->is_user_verified;
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
        $mediaUrl = $this->getFirstMediaUrl('avatar', 'thumb');

        if (filled($mediaUrl)) {
            return $mediaUrl;
        }

        return $this->avatar_placeholder_url;
    }

    /**
     * Placeholder avatara jako data URI SVG z inicjalami.
     */
    public function getAvatarPlaceholderUrlAttribute(): string
    {
        $initials = $this->resolveAvatarInitials();
        $displayName = $this->full_name ?: $this->name ?: $this->email ?: 'Uzytkownik';
        $seed = (string) ($this->email ?: $this->name ?: $this->getKey() ?: $displayName);

        $hash = abs(crc32($seed));
        $hue = $hash % 360;
        $saturation = 58 + ($hash % 10);
        $lightness = 42 + (($hash >> 6) % 10);

        $escapedInitials = htmlspecialchars($initials, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $escapedLabel = htmlspecialchars($displayName, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $background = "hsl({$hue}, {$saturation}%, {$lightness}%)";

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 256 256" role="img" aria-label="{$escapedLabel}">
    <rect width="256" height="256" fill="{$background}" />
    <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="92" font-weight="700">{$escapedInitials}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function resolveAvatarInitials(): string
    {
        $source = trim((string) ($this->full_name ?: $this->name ?: $this->email ?: 'U'));

        if ($source === '') {
            return 'U';
        }

        $rawParts = preg_split('/\s+/u', $source) ?: [];
        $parts = array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            $rawParts,
        )));

        if (count($parts) >= 2) {
            $first = $this->extractAvatarInitial($parts[0]);
            $last = $this->extractAvatarInitial($parts[count($parts) - 1]);
            $initials = $first.$last;
        } else {
            $singlePart = $parts[0] ?? $source;
            $normalized = $this->normalizeAvatarToken($singlePart);
            $initials = mb_substr($normalized, 0, 2);
        }

        $initials = Str::upper(trim($initials));

        return $initials !== '' ? $initials : 'U';
    }

    private function extractAvatarInitial(string $value): string
    {
        $normalized = $this->normalizeAvatarToken($value);

        return $normalized !== '' ? mb_substr($normalized, 0, 1) : '';
    }

    private function normalizeAvatarToken(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return preg_replace('/[^\pL\pN]+/u', '', $value) ?? '';
    }
}
