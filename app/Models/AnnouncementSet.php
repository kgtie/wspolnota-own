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
    // OPCJE DO FORMULARZY
    // =========================================

    /**
     * Statusy zestawów ogłoszeń
     */
    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Szkic',
            'published' => 'Opublikowany',
            'archived' => 'Zarchiwizowany',
        ];
    }

    /**
     * Kolory statusów dla Filament Badge
     */
    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'draft' => 'gray',
            'published' => 'success',
            'archived' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Ikony statusów
     */
    public static function getStatusIcon(string $status): string
    {
        return match ($status) {
            'draft' => 'heroicon-o-pencil',
            'published' => 'heroicon-o-check-circle',
            'archived' => 'heroicon-o-archive-box',
            default => 'heroicon-o-question-mark-circle',
        };
    }

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
    // SCOPES
    // =========================================

    /**
     * Aktualnie obowiązujące ogłoszenia
     */
    public function scopeCurrent(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->where('status', 'published')
            ->where('valid_from', '<=', $today)
            ->where('valid_until', '>=', $today);
    }

    /**
     * Opublikowane ogłoszenia
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Szkice
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Nadchodzące (jeszcze nie obowiązują)
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('valid_from', '>', now()->toDateString());
    }

    /**
     * Przeszłe (już nie obowiązują)
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('valid_until', '<', now()->toDateString());
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

    /**
     * Liczba ogłoszeń w zestawie
     */
    public function getAnnouncementsCountAttribute(): int
    {
        return $this->announcements()->count();
    }

    /**
     * Sformatowany okres obowiązywania
     */
    public function getValidPeriodAttribute(): string
    {
        return $this->valid_from->format('d.m') . ' - ' . $this->valid_until->format('d.m.Y');
    }

    /**
     * Etykieta statusu
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    // =========================================
    // AKCJE
    // =========================================

    /**
     * Opublikuj zestaw
     */
    public function publish(?int $userId = null): bool
    {
        return $this->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Archiwizuj zestaw
     */
    public function archive(): bool
    {
        return $this->update(['status' => 'archived']);
    }

    /**
     * Cofnij do szkicu
     */
    public function unpublish(): bool
    {
        return $this->update([
            'status' => 'draft',
            'published_at' => null,
            'published_by' => null,
        ]);
    }
}
