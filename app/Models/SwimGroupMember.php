<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SwimGroupMember extends Model
{
    protected $fillable = ['swim_group_id','customer_id','guardian_customer_id','joined_on','left_on','status','notes'];
    protected $casts = ['joined_on'=>'date','left_on'=>'date'];
    public function group(){ return $this->belongsTo(SwimGroup::class, 'swim_group_id'); }
    public function customer(){ return $this->belongsTo(Customer::class); }
    public function guardian(){ return $this->belongsTo(Customer::class, 'guardian_customer_id'); }
    public function attendance(){ return $this->hasMany(SwimAttendance::class); }
    public function makeups(){ return $this->hasMany(SwimMakeup::class); }
    public function progress(){ return $this->hasMany(SwimProgress::class)->latest('recorded_on'); }
}
