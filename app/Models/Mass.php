<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

// Importy konieczne dla kalendarza (Guava Calendar) w panelu admina
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Support\Carbon;

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
class Mass extends Model implements Eventable
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
     * Typy mszy (rodzaje)
     */
    public static function getTypeOptions(): array
    {
        return [
            'z dnia' => 'Z dnia (kalendarz liturgiczny)',
            'pogrzebowa' => 'Pogrzebowa',
            'ślubna' => 'Ślubna',
            'chrzcielna' => 'Chrzcielna',
            'pierwszokomunijna' => 'Pierwszokomunijna',
            'bierzmowanie' => 'Z bierzmowaniem',
            'rocznicowa' => 'Rocznicowa',
            'intencyjna' => 'Intencyjna',
            'wotywna' => 'Wotywna',
            'żałobna' => 'Żałobna (requiem)',
            'inna' => 'Inna',
        ];
    }

    /**
     * Typy rytu
     */
    public static function getRiteOptions(): array
    {
        return [
            'rzymski' => 'Ryt rzymski (Novus Ordo)',
            'trydencki' => 'Ryt trydencki (Msza św. wszechczasów)',
        ];
    }

    /**
     * Typowe lokalizacje
     */
    public static function getLocationOptions(): array
    {
        return [
            'Kościół główny',
            'Kaplica',
            'Kaplica cmentarna',
            'Kaplica szpitalna',
            'Dom parafialny',
            'Na zewnątrz',
        ];
    }

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
    // SCOPES
    // =========================================

    /**
     * Nadchodzące msze (od teraz)
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_time', '>=', now())
            ->orderBy('start_time', 'asc');
    }

    /**
     * Msze z przeszłości
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_time', '<', now())
            ->orderBy('start_time', 'desc');
    }

    /**
     * Msze w danym tygodniu
     */
    public function scopeInWeek(Builder $query, ?\Carbon\Carbon $date = null): Builder
    {
        $date = $date ?? now();
        return $query->whereBetween('start_time', [
            $date->copy()->startOfWeek(),
            $date->copy()->endOfWeek(),
        ]);
    }

    /**
     * Msze w danym zakresie dat
     */
    public function scopeBetweenDates(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('start_time', [$from, $to]);
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

    /**
     * Liczba zapisanych osób
     */
    public function getAttendeesCountAttribute(): int
    {
        return $this->attendees()->count();
    }

    /**
     * Sformatowana data i godzina
     */
    public function getFormattedDateTimeAttribute(): string
    {
        return $this->start_time->translatedFormat('l, j F Y, H:i');
    }

    /**
     * Krótka data
     */
    public function getShortDateAttribute(): string
    {
        return $this->start_time->format('d.m.Y');
    }

    /**
     * Sama godzina
     */
    public function getTimeOnlyAttribute(): string
    {
        return $this->start_time->format('H:i');
    }

    /**
     * Nazwa dnia tygodnia
     */
    public function getDayNameAttribute(): string
    {
        return $this->start_time->translatedFormat('l');
    }

    /**
     * Msze dzisiejsze
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('start_time', now()->toDateString());
    }

    /**
     * Etykieta typu mszy
     */
    public function getTypeLabel(): string
    {
        return self::getTypeOptions()[$this->type] ?? $this->type;
    }

    /**
     * Etykieta rytu
     */
    public function getRiteLabel(): string
    {
        return self::getRiteOptions()[$this->rite] ?? $this->rite;
    }

    /**
     * Sformatowane stypendium
     */
    public function getFormattedStipendAttribute(): ?string
    {
        if ($this->stipend === null) {
            return null;
        }
        return number_format($this->stipend, 2, ',', ' ') . ' zł';
    }

    /**
     * Metoda konieczna dla działania pluginu Guava Calendar w Filament w panelu amdina
     * @return CalendarEvent
     */
    public function toCalendarEvent(): CalendarEvent
    {
        $start = Carbon::parse($this->start_time);

        $time = $start->format('H:i');

        // czytelny tytuł:
        $title = "{$time} • " . str($this->intention)->limit(45);

        [$bg, $text] = $this->calendarColors();

        return CalendarEvent::make($this)
            ->title($title)
            ->start($start)
            ->end($start->copy()->addMinutes(45))
            ->backgroundColor($bg)
            ->textColor($text)
            ->action('edit');
    }

    protected function calendarColors(): array
    {
        // dopasuj do swoich realnych typów; to jest bezpieczny start:
        $type = (string) $this->type;
        if ($this->start_time < now()) {
            return ['#6b7280', '#ffffff']; // szary dla przeszłych
        }
        return match ($type) {
            'pogrzeb' => ['#111827', '#ffffff'],
            'ślub', 'slub' => ['#db2777', '#ffffff'],
            'chrzest' => ['#0ea5e9', '#ffffff'],
            'niedzielna' => ['#2563eb', '#ffffff'],
            default => ['#16a34a', '#ffffff'],
        };
    }
}
