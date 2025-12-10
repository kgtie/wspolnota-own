<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'current_parish_id',
    ];

    // --- RELACJE ---

    // Wszystkie parafie, do których user ma dostęp (jako admin)
    public function parishes(): BelongsToMany
    {
        return $this->belongsToMany(Parish::class);
    }

    // Parafia, w której aktualnie "pracuje" (Kontekst)
    public function currentParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'current_parish_id');
    }

    // --- HELPERY DO RÓL ---

    public function isSuperAdmin(): bool
    {
        return $this->role === 2;
    }

    public function isAdmin(): bool
    {
        // Admin (1) lub SuperAdmin (2) mają dostęp do paneli zarządczych
        return $this->role >= 1;
    }
}