<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalChecklist extends Model
{
    protected $fillable = ['name','type','pool_zone_id','is_active'];
    protected $casts = ['is_active'=>'boolean'];
    public function zone(){ return $this->belongsTo(PoolZone::class, 'pool_zone_id'); }
    public function items(){ return $this->hasMany(TechnicalChecklistItem::class)->orderBy('sort_order'); }
    public function runs(){ return $this->hasMany(TechnicalChecklistRun::class)->latest('performed_at'); }
}
