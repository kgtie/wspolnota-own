<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    protected $table = 'media_files';

    protected $fillable = [
        'uploader_user_id', 'parish_id',
        'disk', 'path', 'original_name', 'file_name',
        'mime_type', 'size',
        'visibility', 'meta',
        'attachable_type', 'attachable_id', 'collection',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function parish()
    {
        return $this->belongsTo(Parish::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_user_id');
    }
}
