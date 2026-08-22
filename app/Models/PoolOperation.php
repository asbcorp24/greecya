<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoolOperation extends Model
{
    protected $fillable = ['pool_zone_id','pool_lane_id','user_id','type','performed_at','duration_minutes','details','result'];
    protected $casts = ['performed_at'=>'datetime'];
    public function zone(){ return $this->belongsTo(PoolZone::class, 'pool_zone_id'); }
    public function lane(){ return $this->belongsTo(PoolLane::class, 'pool_lane_id'); }
    public function user(){ return $this->belongsTo(User::class); }
}
