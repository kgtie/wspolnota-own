<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Model AnnouncementSet - Zestaw ogłoszeń parafialnych
 * 
 * Zestaw to "kontener" na pojedyncze ogłoszenia obowiązujące w danym tygodniu.
 * 
 * @property int $id
 * @property int $parish_id
 * @property string $title
 * @property Carbon $valid_from
 * @property Carbon $valid_until
 * @property string|null $ai_summary
 * @property Carbon|null $ai_summary_generated_at
 * @property string $status (draft, published, archived)
 * @property Carbon|null $published_at
 * @property int|null $created_by
 * @property int|null $published_by
 * 
 * @property-read Parish $parish
 * @property-read \Illuminate\Database\Eloquent\Collection|Announcement[] $announcements
 * @property-read User|null $creator
 * @property-read User|null $publisher
 */
class AnnouncementSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'parish_id',
        'title',
        'valid_from',
        'valid_until',
        'ai_summary',
        'ai_summary_generated_at',
        'status',
        'published_at',
        'created_by',
        'published_by',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'published_at' => 'datetime',
        'ai_summary_generated_at' => 'datetime',
    ];

    // =========================================
    // RELACJE
    // =========================================

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    /**
     * Pojedyncze ogłoszenia (uporządkowane wg sort_order)
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    // =========================================
    // HELPERY / ACCESSORS
    // =========================================

    /**
     * Czy zestaw jest aktualnie obowiązujący
     */
    public function isCurrent(): bool
    {
        $today = now()->toDateString();
        return $this->status === 'published'
            && $this->valid_from <= $today
            && $this->valid_until >= $today;
    }

    /**
     * Czy zestaw jest opublikowany
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Czy zestaw jest szkicem
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
