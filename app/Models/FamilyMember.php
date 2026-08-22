<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    protected $fillable = ['family_id','customer_id','relation','is_guardian','can_manage_bookings','can_use_wallet'];
    protected $casts = ['is_guardian'=>'boolean','can_manage_bookings'=>'boolean','can_use_wallet'=>'boolean'];
    public function family(){ return $this->belongsTo(Family::class); }
    public function customer(){ return $this->belongsTo(Customer::class); }
}
