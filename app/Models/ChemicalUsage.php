<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChemicalUsage extends Model
{
    protected $fillable = ['pool_zone_id','inventory_item_id','inventory_batch_id','user_id','quantity','unit','used_at','purpose','notes'];
    protected $casts = ['quantity'=>'decimal:3','used_at'=>'datetime'];
    public function zone(){ return $this->belongsTo(PoolZone::class, 'pool_zone_id')->withTrashed(); }
    public function item(){ return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function batch(){ return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id'); }
    public function user(){ return $this->belongsTo(User::class); }
}
