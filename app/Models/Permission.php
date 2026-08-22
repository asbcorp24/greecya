<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['code','name','group','description','sort_order'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions')->withPivot('allowed')->withTimestamps();
    }
}
