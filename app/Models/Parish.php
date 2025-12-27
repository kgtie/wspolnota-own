<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Parish - Parafie (Klienci usługi)
 * 
 * Parafia jest głównym tenantem w systemie multi-tenancy.
 * Administratorzy zarządzają przypisanymi parafiami,
 * a użytkownicy rejestrują się do konkretnej parafii.
 * 
 * @property int $id
 * @property string $name - Pełna nazwa parafii
 * @property string $short_name - Krótka nazwa (np. "Parafia Wiskitki")
 * @property string $slug - URL slug (np. "wiskitki")
 * @property string|null $diocese - Diecezja
 * @property string|null $decanate - Dekanat
 * @property string|null $street - Ulica
 * @property string|null $city - Miasto
 * @property string|null $postal_code - Kod pocztowy
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $avatar - Logo/zdjęcie parafii
 * @property bool $is_active - Czy parafia jest aktywna
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Parish extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'slug',
        'diocese',
        'decanate',
        'street',
        'city',
        'postal_code',
        'email',
        'phone',
        'website',
        'avatar',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // =========================================
    // RELACJE
    // =========================================

    /**
     * Administratorzy parafii
     * Relacja many-to-many przez tabelę parish_user
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parish_user')
            ->withTimestamps();
    }

    /**
     * Parafianie - użytkownicy z home_parish_id = ta parafia
     */
    public function parishioners(): HasMany
    {
        return $this->hasMany(User::class, 'home_parish_id')
            ->where('role', 0);
    }

    /**
     * Wszyscy użytkownicy powiązani z parafią (parafianie + aktualnie przeglądający)
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'home_parish_id');
    }

    /**
     * Msze święte w parafii (placeholder)
     */
    public function masses(): HasMany
    {
        return $this->hasMany(Mass::class);
    }

    /**
     * Zestawy ogłoszeń parafialnych (placeholder)
     */
    public function announcementSets(): HasMany
    {
        return $this->hasMany(AnnouncementSet::class);
    }

    /**
     * Aktualności/wpisy blogowe parafii (placeholder)
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // =========================================
    // HELPERY
    // =========================================

    /**
     * Pełny adres parafii
     */
    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->street,
            $this->postal_code . ' ' . $this->city,
        ]);

        return count($parts) > 0 ? implode(', ', $parts) : null;
    }

    /**
     * Liczba zweryfikowanych parafian
     */
    public function getVerifiedParishionersCountAttribute(): int
    {
        return $this->parishioners()->where('is_user_verified', true)->count();
    }

    /**
     * Liczba parafian oczekujących na weryfikację
     */
    public function getPendingParishionersCountAttribute(): int
    {
        return $this->parishioners()->where('is_user_verified', false)->count();
    }

    /**
     * Zwraca route key name dla URL
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
