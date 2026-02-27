<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'news_post_id',
        'user_id',
        'body',
    ];

    public function newsPost(): BelongsTo
    {
        return $this->belongsTo(NewsPost::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
