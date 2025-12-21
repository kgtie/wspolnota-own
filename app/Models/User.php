<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password',
        'role',              // 0: User, 1: Admin, 2: SuperAdmin
        'home_parish_id',    // ID parafii domowej (dla parafianina)
        'verification_code', // Kod 9 cyfr
        'is_user_verified',  // Czy zatwierdzony przez proboszcza
        'current_parish_id', // Kontekst sesji (ostatnio wybrana parafia)
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code', // Ukrywamy kod bezpieczeństwa w API/JSON
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_parish_verified' => 'boolean',
            'role' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relacje (Relationships)
    |--------------------------------------------------------------------------
    */

    /**
     * Relacja 1: Parafia domowa (dla ZWYKŁEGO PARAFIANINA).
     * To jest parafia, do której user się zapisał.
     */
    public function homeParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'home_parish_id');
    }

    /**
     * Relacja 2: Parafie zarządzane (dla ADMINISTRATORA).
     * To są parafie, do których user ma dostęp zarządczy (przez tabelę pivot).
     */
    public function managedParishes(): BelongsToMany
    {
        return $this->belongsToMany(Parish::class, 'parish_user')
                    ->withTimestamps();
    }

    /**
     * Relacja 3: Aktualny kontekst (dla UI).
     * Parafia, którą użytkownik aktualnie przegląda lub zarządza.
     */
    public function currentParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'current_parish_id');
    }



    /*
    |--------------------------------------------------------------------------
    | Helpery i Logika Biznesowa
    |--------------------------------------------------------------------------
    */

    public function isSuperAdmin(): bool
    {
        return $this->role === 2;
    }

    public function isAdmin(): bool
    {
        return $this->role >= 1;
    }
    
    // Czy użytkownik jest zatwierdzonym parafianinem w SWOJEJ parafii domowej?
    public function isVerifiedParishioner(): bool
    {
        return $this->is_user_verified === true;
    }
}