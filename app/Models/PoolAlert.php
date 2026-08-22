<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoolAlert extends Model
{
    protected $fillable=['pool_zone_id','pool_water_log_id','parameter','severity','actual_value','expected_range','status','acknowledged_by','acknowledged_at','notes'];
    protected $casts=['acknowledged_at'=>'datetime'];

    public function zone(){ return $this->belongsTo(PoolZone::class,'pool_zone_id')->withTrashed(); }
    public function waterLog(){ return $this->belongsTo(PoolWaterLog::class,'pool_water_log_id'); }
}
