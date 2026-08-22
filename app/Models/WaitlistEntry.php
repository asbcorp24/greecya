<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class WaitlistEntry extends Model { use HasFactory; protected $fillable=['schedule_slot_id','customer_id','people','priority','status','notified_at']; protected $casts=['notified_at'=>'datetime']; public function slot(){return $this->belongsTo(ScheduleSlot::class,'schedule_slot_id');} public function customer(){return $this->belongsTo(Customer::class);} }
