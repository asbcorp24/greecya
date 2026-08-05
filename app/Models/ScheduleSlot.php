<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleSlot extends Model
{
    use HasFactory;

    protected $fillable = ['service_id', 'trainer_id', 'starts_at', 'ends_at', 'capacity', 'booked_count', 'status'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getAvailablePlacesAttribute(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }
}
