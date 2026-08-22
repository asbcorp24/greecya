<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoolZone extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'capacity',
        'is_active',
        'deleted_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function lanes()
    {
        return $this->hasMany(PoolLane::class);
    }

    public function lanesWithTrashed()
    {
        return $this->hasMany(PoolLane::class)->withTrashed();
    }

    public function waterLogs()
    {
        return $this->hasMany(PoolWaterLog::class)->latest('measured_at');
    }

    public function slots()
    {
        return $this->hasMany(ScheduleSlot::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }
}
