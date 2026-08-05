<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'email', 'birth_date', 'notes', 'source', 'last_visit_at'];

    protected $casts = ['birth_date' => 'date', 'last_visit_at' => 'datetime'];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
