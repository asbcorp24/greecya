<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PoolZone extends Model { use HasFactory; protected $fillable=['name','code','type','capacity','is_active']; protected $casts=['is_active'=>'boolean']; public function lanes(){return $this->hasMany(PoolLane::class);} public function waterLogs(){return $this->hasMany(PoolWaterLog::class)->latest('measured_at');} public function slots(){return $this->hasMany(ScheduleSlot::class);} }
