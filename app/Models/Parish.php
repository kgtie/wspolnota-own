<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parish extends Model
{
    protected $fillable = ['name', 'city', 'slug'];

    public function admins()
    {
        return $this->belongsToMany(User::class);
    }
}