<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoolLane extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'pool_zone_id',
        'name',
        'number',
        'length_meters',
        'capacity',
        'status',
        'is_active',
        'deleted_by_user_id',
        'deleted_with_zone',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_with_zone' => 'boolean',
        'length_meters' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    public function zone()
    {
        return $this->belongsTo(PoolZone::class, 'pool_zone_id')->withTrashed();
    }

    public function slots()
    {
        return $this->belongsToMany(ScheduleSlot::class, 'schedule_slot_lane')
            ->withPivot('capacity')
            ->withTimestamps();
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }
}
