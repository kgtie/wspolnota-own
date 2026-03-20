<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Mass extends Model
{
    use HasFactory, LogsActivity;
    use SoftDeletes;

    public const MASS_KIND_OPTIONS = [
        'weekday' => 'Powszednia',
        'sunday' => 'Niedzielna',
        'solemnity' => 'Uroczystosc',
        'votive' => 'Wotywna',
        'requiem' => 'Zalobna',
    ];

    public const MASS_TYPE_OPTIONS = [
        'individual' => 'Indywidualna',
        'collective' => 'Zbiorowa',
        'gregorian' => 'Gregorianska',
        'occasional' => 'Okolicznosciowa',
    ];

    public const STATUS_OPTIONS = [
        'scheduled' => 'Zaplanowana',
        'completed' => 'Odprawiona',
        'cancelled' => 'Odwolana',
    ];

    protected $fillable = [
        'parish_id',
        'intention_title',
        'intention_details',
        'celebration_at',
        'stipendium_amount',
        'stipendium_paid_at',
        'mass_kind',
        'mass_type',
        'status',
        'celebrant_name',
        'location',
        'notes',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'celebration_at' => 'datetime',
            'stipendium_amount' => 'decimal:2',
            'stipendium_paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $mass): void {
            foreach (array_keys($mass->attributes) as $attribute) {
                if (str_ends_with($attribute, '_count')) {
                    unset($mass->attributes[$attribute]);
                }
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'parish_id',
                'intention_title',
                'intention_details',
                'celebration_at',
                'stipendium_amount',
                'stipendium_paid_at',
                'mass_kind',
                'mass_type',
                'status',
                'celebrant_name',
                'location',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Msza zostala {$eventName}");
    }

    public static function getMassKindOptions(): array
    {
        return self::MASS_KIND_OPTIONS;
    }

    public static function getMassTypeOptions(): array
    {
        return self::MASS_TYPE_OPTIONS;
    }

    public static function getStatusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mass_user')
            ->withPivot([
                'registered_at',
                'reminder_push_24h_sent_at',
                'reminder_push_8h_sent_at',
                'reminder_push_1h_sent_at',
                'reminder_email_sent_at',
            ])
            ->withTimestamps();
    }
}
