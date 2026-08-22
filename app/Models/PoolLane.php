<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PoolLane extends Model { use HasFactory; protected $fillable=['pool_zone_id','name','number','length_meters','capacity','status','is_active']; protected $casts=['is_active'=>'boolean','length_meters'=>'decimal:2']; public function zone(){return $this->belongsTo(PoolZone::class,'pool_zone_id');} public function slots(){return $this->belongsToMany(ScheduleSlot::class,'schedule_slot_lane')->withPivot('capacity')->withTimestamps();} }
