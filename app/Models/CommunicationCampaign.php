<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CommunicationCampaign extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_TEMPLATE = 'template';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_DISPATCHING = 'dispatching';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'name',
        'channel',
        'is_template',
        'status',
        'parish_id',
        'created_by_user_id',
        'subject_line',
        'preheader',
        'builder_payload',
        'recipients_total',
        'queued_count',
        'failed_count',
        'scheduled_for',
        'queued_at',
        'sent_at',
        'last_test_sent_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'is_template' => 'boolean',
            'builder_payload' => 'array',
            'scheduled_for' => 'datetime',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'last_test_sent_at' => 'datetime',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('content_images')
            ->useDisk('news')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
