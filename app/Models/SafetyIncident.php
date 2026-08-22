<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SafetyIncident extends Model
{
    protected $fillable = ['number','type','severity','customer_id','pool_zone_id','pool_lane_id','responsible_user_id','occurred_at','description','actions_taken','ambulance_called','lane_closed','photo_path','status','resolution','closed_at'];
    protected $casts = ['occurred_at'=>'datetime','closed_at'=>'datetime','ambulance_called'=>'boolean','lane_closed'=>'boolean'];
    public function customer(){ return $this->belongsTo(Customer::class); }
    public function zone(){ return $this->belongsTo(PoolZone::class, 'pool_zone_id')->withTrashed(); }
    public function lane(){ return $this->belongsTo(PoolLane::class, 'pool_lane_id')->withTrashed(); }
    public function responsible(){ return $this->belongsTo(User::class, 'responsible_user_id'); }
}
