<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'provider', 'external_id', 'status', 'amount', 'payload', 'paid_at'];

    protected $casts = ['amount' => 'decimal:2', 'payload' => 'array', 'paid_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
