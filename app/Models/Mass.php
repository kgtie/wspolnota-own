<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Mass extends Model
{
    use HasFactory;

    protected $fillable = [
        'parish_id',
        'start_time',
        'location',
        'intention',
        'type',
        'rite',
        'celebrant',
        'stipend',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'stipend' => 'decimal:2',
    ];

    /**
     * 
     * RELACJE
     */
    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mass_user')
            ->withTimestamps();
    }
}