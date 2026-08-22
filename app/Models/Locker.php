<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Locker extends Model { use HasFactory; protected $fillable=['number','zone','gender','status','is_active']; protected $casts=['is_active'=>'boolean']; public function rentals(){return $this->hasMany(LockerRental::class);} }
