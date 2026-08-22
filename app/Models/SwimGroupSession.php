<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SwimGroupSession extends Model
{
    protected $fillable = ['swim_group_id','schedule_slot_id','pool_lane_id','starts_at','ends_at','status','notes'];
    protected $casts = ['starts_at'=>'datetime','ends_at'=>'datetime'];
    public function group(){ return $this->belongsTo(SwimGroup::class, 'swim_group_id'); }
    public function slot(){ return $this->belongsTo(ScheduleSlot::class, 'schedule_slot_id'); }
    public function lane(){ return $this->belongsTo(PoolLane::class, 'pool_lane_id'); }
    public function attendance(){ return $this->hasMany(SwimAttendance::class); }
}
