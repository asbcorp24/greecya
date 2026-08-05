<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'customer_id', 'status', 'payment_status', 'subtotal', 'total', 'promo_code', 'source', 'paid_at'];

    protected $casts = ['subtotal' => 'decimal:2', 'total' => 'decimal:2', 'paid_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
