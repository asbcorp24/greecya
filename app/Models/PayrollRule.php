<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PayrollRule extends Model { use HasFactory; protected $fillable=['user_id','trainer_id','service_id','name','calc_type','rate','is_active']; protected $casts=['rate'=>'decimal:2','is_active'=>'boolean']; public function trainer(){return $this->belongsTo(Trainer::class);} public function service(){return $this->belongsTo(Service::class);} }
