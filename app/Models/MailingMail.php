<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailingMail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mailing_list_id',
        'email',
        'confirmation_token',
        'confirmed_at',
        'unsubscribe_token',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }

    public function mailingList(): BelongsTo
    {
        return $this->belongsTo(MailingList::class, 'mailing_list_id');
    }
}
