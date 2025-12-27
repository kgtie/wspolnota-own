<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

/**
 * Model User - Użytkownicy systemu Wspólnota
 * 
 * Obsługuje:
 * - Zwykłych użytkowników (role = 0) - parafianie
 * - Administratorów (role = 1) - zarządzają przypisanymi parafiami
 * - Superadministratorów (role = 2) - pełny dostęp
 * 
 * @property int $id
 * @property string $name
 * @property string|null $full_name
 * @property string $email
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $avatar
 * @property int $role (0=user, 1=admin, 2=superadmin)
 * @property int|null $home_parish_id - Parafia domowa użytkownika
 * @property int|null $current_parish_id - Aktualnie przeglądana parafia
 * @property string|null $verification_code - 9-cyfrowy kod do weryfikacji przez proboszcza
 * @property bool $is_user_verified - Czy użytkownik jest zweryfikowany przez proboszcza
 * @property \Carbon\Carbon|null $user_verified_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class User extends Authenticatable implements FilamentUser, HasTenants, MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password',
        'avatar',
        'role',
        'home_parish_id',
        'current_parish_id',
        'verification_code',
        'is_user_verified',
        'user_verified_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'user_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_user_verified' => 'boolean',
            'role' => 'integer',
        ];
    }

    /**
     * Boot the model - generuje kod weryfikacyjny przy tworzeniu
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (empty($user->verification_code)) {
                $user->verification_code = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            }
        });
    }

    // =========================================
    // RELACJE
    // =========================================

    /**
     * Parafia domowa użytkownika (jako parafianin)
     */
    public function homeParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'home_parish_id');
    }

    /**
     * Aktualnie przeglądana parafia
     */
    public function currentParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'current_parish_id');
    }

    /**
     * Parafie zarządzane przez użytkownika (dla adminów)
     * Relacja many-to-many przez tabelę parish_user
     */
    public function parishes(): BelongsToMany
    {
        return $this->belongsToMany(Parish::class, 'parish_user')
            ->withTimestamps();
    }

    public function managedParishes(): BelongsToMany
    {
        return $this->belongsToMany(Parish::class,'parish_user')->withTimestamps();
    }

    // =========================================
    // FILAMENT INTERFACES
    // =========================================

    /**
     * Czy użytkownik może uzyskać dostęp do panelu Filament
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Superadmin panel - tylko rola 2
        if ($panel->getId() === 'superadmin') {
            return $this->role === 2;
        }

        // Admin panel - rola 1 lub 2, i musi mieć przypisane parafie
        if ($panel->getId() === 'admin') {
            return $this->role >= 1 && $this->parishes()->exists();
        }

        return false;
    }

    /**
     * Zwraca tenanty (parafie) dostępne dla użytkownika
     * Wymagane przez HasTenants interface
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->parishes;
    }

    /**
     * Sprawdza czy użytkownik może uzyskać dostęp do danego tenanta
     * Wymagane przez HasTenants interface
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->parishes()->whereKey($tenant)->exists();
    }

    // =========================================
    // HELPERY
    // =========================================

    /**
     * Czy użytkownik jest superadminem
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 2;
    }

    /**
     * Czy użytkownik jest adminem (lub superadminem)
     */
    public function isAdmin(): bool
    {
        return $this->role >= 1;
    }

    /**
     * Czy użytkownik jest zwykłym parafianinem
     */
    public function isParishioner(): bool
    {
        return $this->role === 0;
    }

    /**
     * Czy użytkownik jest zweryfikowany jako parafianin
     */
    public function isVerified(): bool
    {
        return $this->is_user_verified;
    }

    /**
     * Generuje nowy kod weryfikacyjny
     */
    public function regenerateVerificationCode(): string
    {
        $code = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        $this->update(['verification_code' => $code]);
        return $code;
    }
}
