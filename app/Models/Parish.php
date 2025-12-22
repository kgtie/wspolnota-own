<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parish extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'short_name', 
        'slug', 
        'city', 
        'street', 
        'postal_code', 
        'diocese', 
        'decanate',
        'email', 
        'phone', 
        'website',
        'avatar', 
        'cover_image',
        'is_active', 
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Do routingu używamy sluga (np. /app/wiskitki) zamiast ID.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /*
    |--------------------------------------------------------------------------
    | Relacje
    |--------------------------------------------------------------------------
    */

    /**
     * Administratorzy tej parafii (z tabeli pivot).
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parish_user')
                    ->withTimestamps();
    }

    /**
     * Parafianie (Użytkownicy, którzy zadeklarowali tę parafię jako domową).
     */
    public function parishioners(): HasMany
    {
        return $this->hasMany(User::class, 'home_parish_id');
    }

    /**
     * Msze (wszystkie nadchodzące w danej parafii)
     */
    public function masses(): HasMany
    {
        return $this->hasMany(Mass::class, 'parish_id');
    }
}