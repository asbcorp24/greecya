<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $fillable = ['order_id', 'product_id', 'name', 'quantity', 'price', 'base_price', 'pricing_meta', 'total', 'ticket_code', 'valid_until', 'visits_left'];
    protected $casts = ['price' => 'decimal:2', 'base_price'=>'decimal:2', 'pricing_meta'=>'array', 'total' => 'decimal:2', 'valid_until' => 'date'];
    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function certificates() { return $this->hasMany(Certificate::class); }
}
