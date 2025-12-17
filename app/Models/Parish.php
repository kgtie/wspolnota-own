<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Parish extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'city', 'slug'];

    /**
     * RELACJE
     */
        public function admins()
        {
            return $this->belongsToMany(User::class);
        }
        public function users()
        {
            return $this->hasMany(User::class, 'current_parish_id');
        }
    /**
     * HELPERY
     */
        public function getRouteKeyName()
        {
            return 'slug';
        }
}