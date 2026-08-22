<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class MaintenanceTask extends Model { use HasFactory; protected $fillable=['pool_zone_id','pool_lane_id','assigned_to','title','type','due_at','completed_at','status','notes']; protected $casts=['due_at'=>'datetime','completed_at'=>'datetime']; public function zone(){return $this->belongsTo(PoolZone::class,'pool_zone_id');} public function lane(){return $this->belongsTo(PoolLane::class,'pool_lane_id');} }
