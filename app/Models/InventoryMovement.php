<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = ['inventory_item_id','inventory_batch_id','order_id','pool_zone_id','user_id','type','quantity','unit_cost','occurred_at','note'];
    protected $casts = ['quantity'=>'decimal:3','unit_cost'=>'decimal:2','occurred_at'=>'datetime'];

    public function item(){ return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function batch(){ return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id'); }
    public function zone(){ return $this->belongsTo(PoolZone::class, 'pool_zone_id')->withTrashed(); }
    public function user(){ return $this->belongsTo(User::class); }
}
