<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'booking_id', 'order_item_id', 'visited_at', 'guests', 'source', 'notes'];

    protected $casts = ['visited_at' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
