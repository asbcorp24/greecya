<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalClearanceHistory extends Model
{
    protected $table = 'medical_clearance_history';
    protected $fillable = ['medical_clearance_id','user_id','from_status','to_status','access_blocked','reason','changed_at'];
    protected $casts = ['access_blocked'=>'boolean','changed_at'=>'datetime'];
    public function clearance(){ return $this->belongsTo(MedicalClearance::class, 'medical_clearance_id'); }
    public function user(){ return $this->belongsTo(User::class); }
}
