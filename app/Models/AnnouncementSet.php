<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AnnouncementSet extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_OPTIONS = [
        'draft' => 'Szkic',
        'published' => 'Opublikowany',
        'archived' => 'Archiwalny',
    ];

    protected $fillable = [
        'parish_id',
        'title',
        'week_label',
        'effective_from',
        'effective_to',
        'status',
        'published_at',
        'lead',
        'footer_notes',
        'summary_ai',
        'summary_generated_at',
        'summary_model',
        'notifications_sent_at',
        'notifications_recipients_count',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'published_at' => 'datetime',
            'summary_generated_at' => 'datetime',
            'notifications_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $set): void {
            $contentFields = ['title', 'week_label', 'lead', 'footer_notes', 'effective_from', 'effective_to', 'status'];
            $hasRelevantChanges = collect($contentFields)->contains(fn (string $field): bool => $set->isDirty($field));

            if (! $hasRelevantChanges) {
                return;
            }

            if ($set->isDirty('status') && $set->status !== 'published') {
                return;
            }

            $set->summary_ai = null;
            $set->summary_generated_at = null;
            $set->summary_model = null;
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'parish_id',
                'title',
                'week_label',
                'effective_from',
                'effective_to',
                'status',
                'published_at',
                'lead',
                'footer_notes',
                'summary_ai',
                'summary_generated_at',
                'summary_model',
                'notifications_sent_at',
                'notifications_recipients_count',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Zestaw ogloszen zostal {$eventName}");
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

    public function items(): HasMany
    {
        return $this->hasMany(AnnouncementItem::class)
            ->orderBy('position');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeCurrent($query)
    {
        $today = now()->toDateString();

        return $query
            ->whereDate('effective_from', '<=', $today)
            ->where(function ($inner) use ($today) {
                $inner->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            });
    }

    public function scopeCurrentForDate($query, \DateTimeInterface|string $date)
    {
        $day = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;

        return $query
            ->whereDate('effective_from', '<=', $day)
            ->where(function ($inner) use ($day) {
                $inner->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $day);
            });
    }

    public function publish(?\DateTimeInterface $at = null): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => $at ?? now(),
        ]);
    }

    public function archive(): void
    {
        $this->update([
            'status' => 'archived',
        ]);
    }

    public function isCurrent(\DateTimeInterface|string|null $date = null): bool
    {
        $day = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : ($date ? (string) $date : now()->toDateString());

        if (! $this->effective_from) {
            return false;
        }

        if ($this->effective_from->format('Y-m-d') > $day) {
            return false;
        }

        if ($this->effective_to && $this->effective_to->format('Y-m-d') < $day) {
            return false;
        }

        return true;
    }
}
