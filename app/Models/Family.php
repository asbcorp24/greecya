<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    protected $fillable = ['name','primary_customer_id','status','notes'];
    public function primaryCustomer(){ return $this->belongsTo(Customer::class, 'primary_customer_id'); }
    public function members(){ return $this->hasMany(FamilyMember::class); }
    public function customers(){ return $this->belongsToMany(Customer::class, 'family_members')->withPivot(['relation','is_guardian','can_manage_bookings','can_use_wallet'])->withTimestamps(); }
    public function wallet(){ return $this->hasOne(FamilyWallet::class); }
    public function consents(){ return $this->hasMany(FamilyConsent::class); }
    public function memberships(){ return $this->hasMany(Membership::class); }
}
