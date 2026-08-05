<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'channel', 'request', 'status', 'assigned_to', 'follow_up_at', 'notes'];

    protected $casts = ['follow_up_at' => 'datetime'];

    public function manager()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
