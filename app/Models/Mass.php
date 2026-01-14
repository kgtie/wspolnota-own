<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model Mass - Msze święte z intencjami
 * 
 * @property int $id
 * @property int $parish_id
 * @property \Carbon\Carbon $start_time
 * @property string $location
 * @property string $intention
 * @property string $type
 * @property string $rite
 * @property string|null $celebrant
 * @property float|null $stipend
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
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

    // =========================================
    // RELACJE
    // =========================================

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mass_user')
            ->withTimestamps();
    }

    // =========================================
    // HELPERY
    // =========================================

    /**
     * Czy msza jest w przyszłości
     */
    public function isUpcoming(): bool
    {
        return $this->start_time->isFuture();
    }

    /**
     * Czy msza jest dzisiaj
     */
    public function isToday(): bool
    {
        return $this->start_time->isToday();
    }
}
