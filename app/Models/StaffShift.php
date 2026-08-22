<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class StaffShift extends Model { use HasFactory; protected $fillable=['user_id','trainer_id','starts_at','ends_at','type','status','worked_minutes']; protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime']; public function trainer(){return $this->belongsTo(Trainer::class);} public function user(){return $this->belongsTo(User::class);} }
