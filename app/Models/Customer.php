<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = ['name','phone','email','birth_date','notes','source','last_visit_at','photo_path','gender','emergency_contact','marketing_consent'];
    protected $casts = ['birth_date'=>'date','last_visit_at'=>'datetime','marketing_consent'=>'boolean'];
    public function user(){return $this->hasOne(User::class);}
    public function bookings(){return $this->hasMany(Booking::class);}
    public function orders(){return $this->hasMany(Order::class);}
    public function certificates(){return $this->hasMany(Certificate::class);}
    public function visits(){return $this->hasMany(Visit::class);}
    public function trainingPlans(){return $this->hasMany(TrainingPlan::class);}
    public function progressEntries(){return $this->hasMany(TrainingProgressEntry::class);}
    public function memberships(){return $this->hasMany(Membership::class);}
    public function wallet(){return $this->hasOne(CustomerWallet::class);}
    public function accessCards(){return $this->hasMany(AccessCard::class);}
    public function medicalClearances(){return $this->hasMany(MedicalClearance::class);}
    public function lockerRentals(){return $this->hasMany(LockerRental::class);}
    public function interactions(){return $this->hasMany(CustomerInteraction::class)->latest('occurred_at');}
    public function crmTasks(){return $this->hasMany(CrmTask::class);}
    public function documents(){return $this->hasMany(CustomerDocument::class);}
}
