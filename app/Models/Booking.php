<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = ['public_id', 'customer_id', 'service_id', 'schedule_slot_id', 'trainer_id', 'people', 'total', 'status', 'payment_status', 'comment', 'source', 'confirmed_at', 'cancelled_at'];

    protected $casts = ['total' => 'decimal:2', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function slot()
    {
        return $this->belongsTo(ScheduleSlot::class, 'schedule_slot_id');
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }
}
