<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsComment extends Model
{
    protected $fillable = [
        'news_post_id', 'user_id', 'content', 'status',
    ];

    public function post()
    {
        return $this->belongsTo(NewsPost::class, 'news_post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_comment_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_comment_id');
    }
}
