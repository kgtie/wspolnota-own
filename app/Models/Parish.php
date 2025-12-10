<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Parish extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'city', 'slug'];

    public function admins()
    {
        return $this->belongsToMany(User::class);
    }
}