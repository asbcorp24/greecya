<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryBatch extends Model
{
    protected $fillable = ['inventory_item_id','batch_number','supplier','manufactured_on','expires_on','received_qty','remaining_qty','unit_cost','received_at','document_number','status'];
    protected $casts = ['manufactured_on'=>'date','expires_on'=>'date','received_at'=>'datetime','received_qty'=>'decimal:3','remaining_qty'=>'decimal:3','unit_cost'=>'decimal:2'];
    public function item(){ return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function movements(){ return $this->hasMany(InventoryMovement::class); }
}
