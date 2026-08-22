<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class LockerRental extends Model { use HasFactory; protected $fillable=['locker_id','customer_id','membership_id','started_at','ends_at','returned_at','status','deposit']; protected $casts=['started_at'=>'datetime','ends_at'=>'datetime','returned_at'=>'datetime','deposit'=>'decimal:2']; public function locker(){return $this->belongsTo(Locker::class);} public function customer(){return $this->belongsTo(Customer::class);} }
