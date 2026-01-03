<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Announcement - Pojedyncze ogłoszenie parafialne
 * 
 * Każde ogłoszenie należy do zestawu ogłoszeń (AnnouncementSet).
 * 
 * @property int $id
 * @property int $announcement_set_id
 * @property string $content
 * @property int $sort_order
 * @property bool $is_highlighted
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read AnnouncementSet $announcementSet
 */
class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_set_id',
        'content',
        'sort_order',
        'is_highlighted',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_highlighted' => 'boolean',
    ];

    // =========================================
    // RELACJE
    // =========================================

    /**
     * Zestaw ogłoszeń, do którego należy to ogłoszenie
     */
    public function announcementSet(): BelongsTo
    {
        return $this->belongsTo(AnnouncementSet::class);
    }

    /**
     * Parafia (przez zestaw ogłoszeń)
     */
    public function getParishAttribute(): ?Parish
    {
        return $this->announcementSet?->parish;
    }

    // =========================================
    // HELPERY
    // =========================================

    /**
     * Skrócona treść ogłoszenia (dla podglądu)
     */
    public function getShortContentAttribute(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->content), 100);
    }
}
