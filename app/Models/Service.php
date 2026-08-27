<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'category', 'description', 'duration_minutes', 'price', 'capacity',
        'requires_trainer', 'online_booking', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'requires_trainer' => 'boolean',
        'online_booking' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function slots()
    {
        return $this->hasMany(ScheduleSlot::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
