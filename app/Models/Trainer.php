<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'specialization', 'phone', 'bio', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function slots()
    {
        return $this->hasMany(ScheduleSlot::class);
    }
}
