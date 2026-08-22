<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class InventoryItem extends Model { use HasFactory; protected $fillable=['sku','name','category','unit','purchase_price','sale_price','stock_qty','min_stock','track_marking','is_active']; protected $casts=['purchase_price'=>'decimal:2','sale_price'=>'decimal:2','stock_qty'=>'decimal:3','min_stock'=>'decimal:3','track_marking'=>'boolean','is_active'=>'boolean']; public function movements(){return $this->hasMany(InventoryMovement::class);} }
