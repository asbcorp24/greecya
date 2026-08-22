<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoolWaterLog extends Model
{
    use HasFactory;

    protected $fillable = ['pool_zone_id','user_id','measured_at','temperature','ph','free_chlorine','redox','turbidity','notes'];
    protected $casts = ['measured_at'=>'datetime','temperature'=>'decimal:2','ph'=>'decimal:2','free_chlorine'=>'decimal:3','redox'=>'decimal:2','turbidity'=>'decimal:3'];

    public function zone(){ return $this->belongsTo(PoolZone::class,'pool_zone_id')->withTrashed(); }
}
