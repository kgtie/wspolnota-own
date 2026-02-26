<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class OfficeMessage extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'office_conversation_id',
        'sender_user_id',
        'body',
        'has_attachments',
        'read_by_parishioner_at',
        'read_by_priest_at',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
            'has_attachments' => 'boolean',
            'read_by_parishioner_at' => 'datetime',
            'read_by_priest_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('office');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(OfficeConversation::class, 'office_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id')->withTrashed();
    }
}
