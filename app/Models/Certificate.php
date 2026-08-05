<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;
    protected $fillable = ['serial', 'token', 'order_item_id', 'customer_id', 'product_id', 'recipient_name', 'sender_name', 'message', 'amount', 'status', 'valid_from', 'valid_until', 'redeemed_at', 'redeemed_by', 'notes'];
    protected $casts = ['amount' => 'decimal:2', 'valid_from' => 'date', 'valid_until' => 'date', 'redeemed_at' => 'datetime'];
    public function getRouteKeyName(): string { return 'token'; }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function orderItem() { return $this->belongsTo(OrderItem::class); }
    public function redeemedByUser() { return $this->belongsTo(User::class, 'redeemed_by'); }
    public function isUsable(): bool
    {
        return $this->status === 'active' && (! $this->valid_from || $this->valid_from->lte(today())) && (! $this->valid_until || $this->valid_until->gte(today()));
    }
}
