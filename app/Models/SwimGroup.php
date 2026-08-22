<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SwimGroup extends Model
{
    protected $fillable = ['name','code','age_min','age_max','level','trainer_id','pool_zone_id','pool_lane_id','season_start','season_end','max_members','status','notes'];
    protected $casts = ['season_start'=>'date','season_end'=>'date'];
    public function trainer(){ return $this->belongsTo(Trainer::class); }
    public function zone(){ return $this->belongsTo(PoolZone::class, 'pool_zone_id')->withTrashed(); }
    public function lane(){ return $this->belongsTo(PoolLane::class, 'pool_lane_id')->withTrashed(); }
    public function members(){ return $this->hasMany(SwimGroupMember::class); }
    public function sessions(){ return $this->hasMany(SwimGroupSession::class)->orderBy('starts_at'); }
}
