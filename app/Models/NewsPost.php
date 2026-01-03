<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsPost extends Model
{
    protected $fillable = [
        'parish_id', 'author_user_id',
        'title', 'slug', 'excerpt', 'content',
        'status', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function parish()
    {
        return $this->belongsTo(Parish::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NewsComment::class);
    }

    public function media()
    {
        return $this->morphMany(MediaFile::class, 'attachable');
    }
}
