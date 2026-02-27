<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'news_push',
        'news_email',
        'announcements_push',
        'announcements_email',
        'office_messages_push',
        'office_messages_email',
        'parish_approval_status_push',
        'parish_approval_status_email',
        'auth_security_push',
        'auth_security_email',
    ];

    protected function casts(): array
    {
        return [
            'news_push' => 'boolean',
            'news_email' => 'boolean',
            'announcements_push' => 'boolean',
            'announcements_email' => 'boolean',
            'office_messages_push' => 'boolean',
            'office_messages_email' => 'boolean',
            'parish_approval_status_push' => 'boolean',
            'parish_approval_status_email' => 'boolean',
            'auth_security_push' => 'boolean',
            'auth_security_email' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
