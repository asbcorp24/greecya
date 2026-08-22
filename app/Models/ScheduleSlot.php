<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleSlot extends Model
{
    use HasFactory;

    protected $fillable=['service_id','trainer_id','pool_zone_id','session_type','starts_at','ends_at','capacity','booked_count','status','online_booking','waitlist_capacity'];
    protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime','online_booking'=>'boolean'];

    public function service(){ return $this->belongsTo(Service::class); }
    public function trainer(){ return $this->belongsTo(Trainer::class); }
    public function zone(){ return $this->belongsTo(PoolZone::class,'pool_zone_id')->withTrashed(); }
    public function lanes(){ return $this->belongsToMany(PoolLane::class,'schedule_slot_lane')->withTrashed()->withPivot('capacity')->withTimestamps(); }
    public function bookings(){ return $this->hasMany(Booking::class); }
    public function waitlist(){ return $this->hasMany(WaitlistEntry::class)->orderBy('priority')->oldest(); }
    public function getAvailablePlacesAttribute(): int { return max(0, $this->capacity-$this->booked_count); }
}
