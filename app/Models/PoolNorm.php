<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PoolNorm extends Model { protected $fillable=['pool_zone_id','temperature_min','temperature_max','ph_min','ph_max','free_chlorine_min','free_chlorine_max','redox_min','redox_max','turbidity_max','alerts_enabled']; protected $casts=['alerts_enabled'=>'boolean']; public function zone(){return $this->belongsTo(PoolZone::class,'pool_zone_id');} }
