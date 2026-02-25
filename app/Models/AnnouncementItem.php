<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AnnouncementItem extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'announcement_set_id',
        'position',
        'title',
        'content',
        'is_important',
        'is_active',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_important' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            if ($item->position > 0) {
                if (Auth::id()) {
                    $item->created_by_user_id = $item->created_by_user_id ?: Auth::id();
                    $item->updated_by_user_id = $item->updated_by_user_id ?: Auth::id();
                }

                return;
            }

            $maxPosition = static::query()
                ->where('announcement_set_id', $item->announcement_set_id)
                ->max('position');

            $item->position = ((int) $maxPosition) + 1;

            if (Auth::id()) {
                $item->created_by_user_id = $item->created_by_user_id ?: Auth::id();
                $item->updated_by_user_id = $item->updated_by_user_id ?: Auth::id();
            }
        });

        static::updating(function (self $item): void {
            if (Auth::id()) {
                $item->updated_by_user_id = Auth::id();
            }
        });

        static::saved(function (self $item): void {
            $item->announcementSet()->update([
                'summary_ai' => null,
                'summary_generated_at' => null,
                'summary_model' => null,
            ]);
        });

        static::deleted(function (self $item): void {
            $item->announcementSet()->update([
                'summary_ai' => null,
                'summary_generated_at' => null,
                'summary_model' => null,
            ]);
        });

        static::restored(function (self $item): void {
            $item->announcementSet()->update([
                'summary_ai' => null,
                'summary_generated_at' => null,
                'summary_model' => null,
            ]);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'announcement_set_id',
                'position',
                'title',
                'content',
                'is_important',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Pojedyncze ogloszenie zostalo {$eventName}");
    }

    public function announcementSet(): BelongsTo
    {
        return $this->belongsTo(AnnouncementSet::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function moveUp(): void
    {
        if ($this->position <= 1) {
            return;
        }

        $sibling = static::query()
            ->where('announcement_set_id', $this->announcement_set_id)
            ->where('position', '<', $this->position)
            ->orderByDesc('position')
            ->first();

        if (! $sibling) {
            return;
        }

        DB::transaction(function () use ($sibling): void {
            $currentPosition = $this->position;

            $this->update([
                'position' => $sibling->position,
            ]);

            $sibling->update([
                'position' => $currentPosition,
            ]);
        });

        $this->refresh();
    }

    public function moveDown(): void
    {
        $sibling = static::query()
            ->where('announcement_set_id', $this->announcement_set_id)
            ->where('position', '>', $this->position)
            ->orderBy('position')
            ->first();

        if (! $sibling) {
            return;
        }

        DB::transaction(function () use ($sibling): void {
            $currentPosition = $this->position;

            $this->update([
                'position' => $sibling->position,
            ]);

            $sibling->update([
                'position' => $currentPosition,
            ]);
        });

        $this->refresh();
    }
}
